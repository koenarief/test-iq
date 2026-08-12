<?php

namespace App\Services\Ist;

use App\Exceptions\Ist\IncompleteIstSnapshotException;
use App\Exceptions\Ist\InvalidIstQuestionCountException;
use App\Exceptions\Ist\InvalidIstQuestionDefinitionException;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstSubtest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstAnswerType;
use App\Support\Ist\IstMeRuntimeContent;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class IstQuestionSnapshotService
{
    public function __construct(
        private readonly IstMeRuntimeContent $meRuntimeContent,
    ) {}

    /**
     * The returned models contain server-only answer keys. They must never be
     * passed directly to participant-facing serializers or Inertia payloads.
     */
    public function snapshot(IstTestSubtest $testSubtest): Collection
    {
        if (! $testSubtest->exists || ! $testSubtest->getKey()) {
            throw new DomainException('IST runtime subtest must exist before creating snapshots.');
        }

        return DB::transaction(function () use ($testSubtest): Collection {
            $runtime = IstTestSubtest::query()
                ->with('subtest')
                ->lockForUpdate()
                ->find($testSubtest->getKey());

            if (! $runtime || ! $runtime->subtest) {
                throw new DomainException('IST runtime subtest has no valid master subtest.');
            }

            if ($runtime->locked_at !== null
                || in_array($runtime->status, [
                    IstTestSubtest::STATUS_COMPLETED,
                    IstTestSubtest::STATUS_TIMED_OUT,
                ], true)) {
                throw new DomainException('IST runtime subtest is locked.');
            }

            $existing = $runtime->testQuestions()
                ->orderBy('display_order')
                ->get();

            if ($existing->count() === $runtime->question_count) {
                return $existing;
            }

            if ($existing->isNotEmpty()) {
                throw new IncompleteIstSnapshotException(
                    $runtime->id,
                    $runtime->question_count,
                    $existing->count(),
                );
            }

            $questions = IstQuestion::query()
                ->where('ist_subtest_id', $runtime->ist_subtest_id)
                ->where('kind', IstQuestion::KIND_SCORED)
                ->where('is_active', true)
                ->with(['options' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('display_order')])
                ->orderBy('display_order')
                ->get();

            if ($questions->count() !== $runtime->question_count) {
                throw new InvalidIstQuestionCountException(
                    $runtime->subtest->code,
                    $runtime->question_count,
                    $questions->count(),
                );
            }

            $meRuntime = $runtime->subtest->code === 'ME'
                ? $this->meRuntimeContent->fromMaster($runtime->subtest)
                : null;

            foreach ($questions as $question) {
                [$optionsSnapshot, $answerKeySnapshot] = $this->buildAnswerSnapshots(
                    $runtime->subtest->code,
                    $question,
                );

                if ($meRuntime !== null) {
                    $this->assertMeQuestionContract(
                        $runtime->subtest,
                        $question,
                        $optionsSnapshot,
                        $answerKeySnapshot,
                        $meRuntime,
                    );
                }

                $runtime->testQuestions()->create([
                    'source_question_id' => $question->id,
                    'display_order' => $question->display_order,
                    'answer_type' => $question->answer_type,
                    'question_snapshot' => [
                        'prompt' => $question->prompt,
                        'image_disk' => $question->image_disk,
                        'image_path' => $question->image_path,
                        'image_alt' => $question->image_alt,
                        'source_version' => $question->version,
                        'source_question_number' => $question->question_number,
                        ...($meRuntime === null ? [] : ['me_runtime' => $meRuntime]),
                    ],
                    'options_snapshot' => $optionsSnapshot,
                    'answer_key_snapshot' => $answerKeySnapshot,
                    'max_score' => $question->max_score,
                    'difficulty' => $this->validatedDifficulty(
                        $runtime->subtest->code,
                        $question,
                    ),
                ]);
            }

            return $runtime->testQuestions()
                ->orderBy('display_order')
                ->get();
        });
    }

    private function validatedDifficulty(
        string $subtestCode,
        IstQuestion $question,
    ): string {
        $difficulty = strtolower(trim((string) $question->difficulty));

        if (! in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'difficulty must be easy, medium, or hard',
            );
        }

        return $difficulty;
    }

    private function assertMeQuestionContract(
        IstSubtest $subtest,
        IstQuestion $question,
        array $options,
        array $answerKey,
        array $runtimeContent,
    ): void {
        if (preg_match_all(
            '/huruf\s+permulaan\s+[“"]?(\p{L})[”"]?/ui',
            (string) $question->prompt,
            $matches,
        ) !== 1) {
            throw $this->invalidDefinition(
                $subtest->code,
                $question,
                'ME prompt must contain exactly one target initial',
            );
        }

        $targetInitial = mb_strtoupper($matches[1][0], 'UTF-8');
        $target = null;
        $categoryNames = [];

        foreach ($runtimeContent['groups'] as $group) {
            $categoryNames[$group['key']] = $group['name'];

            foreach ($group['words'] as $word) {
                $initial = mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8');

                if ($initial === $targetInitial) {
                    $target = ['word' => $word, 'groupKey' => $group['key']];
                }
            }
        }

        if ($target === null
            || ($answerKey['correct_option_key'] ?? null) !== $target['groupKey']
            || mb_stripos((string) $question->prompt, $target['word'], 0, 'UTF-8') !== false) {
            throw $this->invalidDefinition(
                $subtest->code,
                $question,
                'ME target initial, hidden word, and answer key are inconsistent',
            );
        }

        foreach ($options as $index => $option) {
            $expectedKey = chr(ord('A') + $index);

            if (($option['option_key'] ?? null) !== $expectedKey
                || ($option['option_text'] ?? null) !== ($categoryNames[$expectedKey] ?? null)) {
                throw $this->invalidDefinition(
                    $subtest->code,
                    $question,
                    'ME options must be the five memorization categories',
                );
            }
        }
    }

    private function buildAnswerSnapshots(string $subtestCode, IstQuestion $question): array
    {
        return match ($question->answer_type) {
            IstAnswerType::SINGLE_CHOICE,
            IstAnswerType::IMAGE_CHOICE => $this->buildBinarySnapshots($subtestCode, $question),
            IstAnswerType::SINGLE_CHOICE_WEIGHTED => $this->buildWeightedSnapshots($subtestCode, $question),
            IstAnswerType::NUMERIC => $this->buildNumericSnapshots($subtestCode, $question),
            default => throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'unsupported answer type',
            ),
        };
    }

    private function buildBinarySnapshots(string $subtestCode, IstQuestion $question): array
    {
        $options = $question->options;
        $this->assertFiveOptions($subtestCode, $question, $options);

        $correctOptions = $options->filter(fn ($option) => $option->is_correct);

        if ($correctOptions->count() !== 1) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'binary question must have exactly one correct option',
            );
        }

        $scores = [];

        foreach ($options as $option) {
            $score = $this->integerScore($subtestCode, $question, $option->score_value);

            if (! in_array($score, [0, 1], true)) {
                throw $this->invalidDefinition(
                    $subtestCode,
                    $question,
                    'binary option score must be 0 or 1',
                );
            }

            if (($score === 1) !== $option->is_correct) {
                throw $this->invalidDefinition(
                    $subtestCode,
                    $question,
                    'binary score and correct flag are inconsistent',
                );
            }

            $scores[$option->option_key] = $score;
        }

        return [
            $this->safeOptions($options),
            [
                'correct_option_key' => $correctOptions->first()->option_key,
                'scores' => $scores,
            ],
        ];
    }

    private function buildWeightedSnapshots(string $subtestCode, IstQuestion $question): array
    {
        $options = $question->options;
        $this->assertFiveOptions($subtestCode, $question, $options);

        $scores = [];
        $outcomes = [];
        $maximumScoreCount = 0;

        foreach ($options as $option) {
            $score = $this->integerScore($subtestCode, $question, $option->score_value);

            if ($score < 0 || $score > 3) {
                throw $this->invalidDefinition(
                    $subtestCode,
                    $question,
                    'weighted option score must be an integer from 0 to 3',
                );
            }

            if ($score === 3) {
                $maximumScoreCount++;
            }

            if (($score === 3) !== $option->is_correct) {
                throw $this->invalidDefinition(
                    $subtestCode,
                    $question,
                    'weighted score and correct flag are inconsistent',
                );
            }

            $scores[$option->option_key] = $score;
            $outcomes[$option->option_key] = match (true) {
                $score === 3 => 'correct',
                $score >= 1 => 'partial',
                default => 'wrong',
            };
        }

        if ($maximumScoreCount !== 1) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'weighted question must have exactly one option with score 3',
            );
        }

        return [
            $this->safeOptions($options),
            [
                'scores' => $scores,
                'outcomes' => $outcomes,
            ],
        ];
    }

    private function buildNumericSnapshots(string $subtestCode, IstQuestion $question): array
    {
        if ($question->numeric_answer_key === null) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'numeric answer key is required',
            );
        }

        return [
            [],
            ['numeric_answer' => $this->canonicalNumeric($subtestCode, $question)],
        ];
    }

    private function assertFiveOptions(
        string $subtestCode,
        IstQuestion $question,
        Collection $options,
    ): void {
        if ($options->count() !== 5) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'choice question must have exactly five active options',
            );
        }
    }

    private function safeOptions(Collection $options): array
    {
        return $options
            ->map(fn ($option) => [
                'option_key' => $option->option_key,
                'option_text' => $option->option_text,
                'image_disk' => $option->image_disk,
                'image_path' => $option->image_path,
                'image_alt' => $option->image_alt,
                'display_order' => $option->display_order,
            ])
            ->values()
            ->all();
    }

    private function integerScore(
        string $subtestCode,
        IstQuestion $question,
        mixed $value,
    ): int {
        $score = trim((string) $value);

        if (! preg_match('/^[+-]?\d+(?:\.0+)?$/', $score)) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'option score must be an integer',
            );
        }

        return (int) $score;
    }

    private function canonicalNumeric(string $subtestCode, IstQuestion $question): string
    {
        $value = trim((string) $question->numeric_answer_key);

        if (! preg_match('/^[+-]?(?:\d+(?:\.\d{0,6})?|\.\d{1,6})$/', $value)) {
            throw $this->invalidDefinition(
                $subtestCode,
                $question,
                'numeric answer key cannot be normalized',
            );
        }

        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = str_pad($fraction, 6, '0');

        if ($integer === '0' && trim($fraction, '0') === '') {
            $negative = false;
        }

        return ($negative ? '-' : '').$integer.'.'.$fraction;
    }

    private function invalidDefinition(
        string $subtestCode,
        IstQuestion $question,
        string $mismatch,
    ): InvalidIstQuestionDefinitionException {
        return new InvalidIstQuestionDefinitionException(
            $subtestCode,
            $question->question_number ?: $question->id,
            $mismatch,
        );
    }
}
