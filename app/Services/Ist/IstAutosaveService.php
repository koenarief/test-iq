<?php

namespace App\Services\Ist;

use App\Data\Ist\IstAutosaveResult;
use App\Enums\Ist\IstSubtestPhase;
use App\Exceptions\Ist\IncompleteIstSnapshotException;
use App\Exceptions\Ist\InvalidIstAutosaveException;
use App\Exceptions\Ist\IstAnswerRevisionConflictException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class IstAutosaveService
{
    private const BASE_FIELDS = [
        'ist_test_question_id',
        'client_revision',
    ];

    public function __construct(
        private readonly IstTimerService $timer,
    ) {}

    public function save(
        IstTestSubtest $testSubtest,
        array $changes,
        CarbonInterface $now,
    ): IstAutosaveResult {
        if (! $testSubtest->exists || ! $testSubtest->getKey()) {
            throw new InvalidIstAutosaveException(0, 'runtime subtest does not exist');
        }

        return DB::transaction(function () use ($testSubtest, $changes, $now): IstAutosaveResult {
            $runtime = IstTestSubtest::query()
                ->lockForUpdate()
                ->find($testSubtest->getKey());

            if (! $runtime) {
                throw new InvalidIstAutosaveException(
                    (int) $testSubtest->getKey(),
                    'runtime subtest is unavailable',
                );
            }

            $test = IstTest::query()
                ->lockForUpdate()
                ->find($runtime->ist_test_id);

            if (! $test) {
                throw new InvalidIstAutosaveException($runtime->id, 'parent test is unavailable');
            }

            $this->assertRuntimeAcceptsAnswers($runtime, $test, $now);

            $questions = $runtime->testQuestions()
                ->orderBy('id')
                ->get()
                ->keyBy('id');

            if ($questions->count() !== $runtime->question_count) {
                throw new IncompleteIstSnapshotException(
                    $runtime->id,
                    $runtime->question_count,
                    $questions->count(),
                );
            }

            $normalized = $this->validateAndNormalizeBatch($runtime, $questions, $changes);
            ksort($normalized, SORT_NUMERIC);

            $saved = [];
            $stale = [];
            $idempotent = [];
            $savedAt = CarbonImmutable::instance($now);

            foreach ($normalized as $questionId => $change) {
                $answer = IstAnswer::query()
                    ->where('ist_test_question_id', $questionId)
                    ->lockForUpdate()
                    ->first();

                if ($answer !== null) {
                    if ($change['client_revision'] < $answer->client_revision) {
                        $stale[] = $questionId;

                        continue;
                    }

                    if ($change['client_revision'] === $answer->client_revision) {
                        if (! $this->payloadMatches($answer, $change)) {
                            throw new IstAnswerRevisionConflictException($runtime->id, $questionId);
                        }

                        $idempotent[] = $questionId;

                        continue;
                    }
                }

                $values = [
                    'selected_option_key' => $change['selected_option_key'],
                    'numeric_answer' => $change['numeric_answer'],
                    'awarded_score' => null,
                    'outcome' => null,
                    'client_revision' => $change['client_revision'],
                    'saved_at' => $savedAt,
                ];

                if ($answer === null) {
                    IstAnswer::create([
                        'ist_test_question_id' => $questionId,
                        ...$values,
                    ]);
                } else {
                    $answer->update($values);
                }

                $saved[] = $questionId;
            }

            if ($saved !== []) {
                $runtime->update(['last_autosaved_at' => $savedAt]);
            }

            return new IstAutosaveResult(
                savedQuestionIds: $saved,
                ignoredStaleQuestionIds: $stale,
                idempotentQuestionIds: $idempotent,
                savedAt: $savedAt,
                remainingSeconds: $this->timer->getRemainingSeconds($runtime, $now),
            );
        });
    }

    private function assertRuntimeAcceptsAnswers(
        IstTestSubtest $runtime,
        IstTest $test,
        CarbonInterface $now,
    ): void {
        if ($test->status !== IstTest::STATUS_IN_PROGRESS || $test->started_at === null) {
            throw new InvalidIstAutosaveException($runtime->id, 'parent test is not in progress');
        }

        if ($test->current_subtest_sequence !== $runtime->sequence) {
            throw new InvalidIstAutosaveException($runtime->id, 'subtest is not current');
        }

        if ($runtime->locked_at !== null || in_array($runtime->status, [
            IstTestSubtest::STATUS_COMPLETED,
            IstTestSubtest::STATUS_TIMED_OUT,
        ], true)) {
            throw new InvalidIstAutosaveException($runtime->id, 'subtest is locked');
        }

        if ($this->timer->getCurrentPhase($runtime, $now) !== IstSubtestPhase::ANSWERING
            || $this->timer->isExpired($runtime, $now)) {
            throw new InvalidIstAutosaveException($runtime->id, 'subtest is not accepting answers');
        }
    }

    private function validateAndNormalizeBatch(
        IstTestSubtest $runtime,
        $questions,
        array $changes,
    ): array {
        $normalized = [];

        foreach ($changes as $change) {
            if (! is_array($change)) {
                throw new InvalidIstAutosaveException($runtime->id, 'batch item must be an object');
            }

            $hasChoice = array_key_exists('selected_option_key', $change);
            $hasNumeric = array_key_exists('numeric_answer', $change);

            if ($hasChoice === $hasNumeric) {
                throw new InvalidIstAutosaveException(
                    $runtime->id,
                    'batch item must contain exactly one answer field',
                );
            }

            $allowed = [...self::BASE_FIELDS, $hasChoice ? 'selected_option_key' : 'numeric_answer'];

            if (array_diff(array_keys($change), $allowed) !== []) {
                throw new InvalidIstAutosaveException($runtime->id, 'batch item contains a forbidden field');
            }

            $questionId = $change['ist_test_question_id'] ?? null;
            $revision = $change['client_revision'] ?? null;

            if (! is_int($questionId) || $questionId <= 0) {
                throw new InvalidIstAutosaveException($runtime->id, 'question ID is invalid');
            }

            if (array_key_exists($questionId, $normalized)) {
                throw new InvalidIstAutosaveException($runtime->id, 'batch contains a duplicate question ID');
            }

            if (! is_int($revision) || $revision < 0) {
                throw new InvalidIstAutosaveException($runtime->id, 'client revision is invalid');
            }

            /** @var IstTestQuestion|null $question */
            $question = $questions->get($questionId);

            if (! $question) {
                throw new InvalidIstAutosaveException($runtime->id, 'question does not belong to subtest');
            }

            if ($question->answer_type === IstAnswerType::NUMERIC) {
                if (! $hasNumeric) {
                    throw new InvalidIstAutosaveException($runtime->id, 'answer field does not match question type');
                }

                $selectedOptionKey = null;
                $numericAnswer = $this->normalizeNumericAnswer($runtime, $change['numeric_answer']);
            } else {
                if (! in_array($question->answer_type, [
                    IstAnswerType::SINGLE_CHOICE,
                    IstAnswerType::SINGLE_CHOICE_WEIGHTED,
                    IstAnswerType::IMAGE_CHOICE,
                ], true) || ! $hasChoice) {
                    throw new InvalidIstAutosaveException($runtime->id, 'answer field does not match question type');
                }

                $selectedOptionKey = $this->validateOptionKey(
                    $runtime,
                    $question,
                    $change['selected_option_key'],
                );
                $numericAnswer = null;
            }

            $normalized[$questionId] = [
                'client_revision' => $revision,
                'selected_option_key' => $selectedOptionKey,
                'numeric_answer' => $numericAnswer,
            ];
        }

        return $normalized;
    }

    private function validateOptionKey(
        IstTestSubtest $runtime,
        IstTestQuestion $question,
        mixed $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        if (! is_string($value) || $value === '' || mb_strlen($value) > 10) {
            throw new InvalidIstAutosaveException($runtime->id, 'selected option key is invalid');
        }

        $availableKeys = array_map(
            static fn (array $option): mixed => $option['option_key'] ?? null,
            $question->options_snapshot ?? [],
        );

        if (! in_array($value, $availableKeys, true)) {
            throw new InvalidIstAutosaveException($runtime->id, 'selected option is unavailable');
        }

        return $value;
    }

    private function normalizeNumericAnswer(IstTestSubtest $runtime, mixed $value): ?string
    {
        if ($value === null || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            throw new InvalidIstAutosaveException($runtime->id, 'numeric answer is invalid');
        }

        $number = trim((string) $value);

        if (! preg_match('/^[+-]?(?:\d+(?:\.\d{0,6})?|\.\d{1,6})$/', $number)) {
            throw new InvalidIstAutosaveException($runtime->id, 'numeric answer is invalid');
        }

        $negative = str_starts_with($number, '-');
        $unsigned = ltrim($number, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;

        if (strlen($integer) > 14) {
            throw new InvalidIstAutosaveException($runtime->id, 'numeric answer exceeds storage precision');
        }

        $fraction = rtrim($fraction, '0');
        $canonical = $fraction === '' ? $integer : $integer.'.'.$fraction;

        if ($canonical === '0') {
            return '0';
        }

        return $negative ? '-'.$canonical : $canonical;
    }

    private function payloadMatches(IstAnswer $answer, array $change): bool
    {
        if ($change['selected_option_key'] !== null || $answer->selected_option_key !== null) {
            return $answer->selected_option_key === $change['selected_option_key']
                && $answer->numeric_answer === null
                && $change['numeric_answer'] === null;
        }

        if ($answer->numeric_answer === null || $change['numeric_answer'] === null) {
            return $answer->numeric_answer === null && $change['numeric_answer'] === null;
        }

        return $this->canonicalStoredNumeric($answer->numeric_answer)
            === $this->canonicalStoredNumeric($change['numeric_answer']);
    }

    private function canonicalStoredNumeric(int|float|string $value): string
    {
        $number = trim((string) $value);
        $negative = str_starts_with($number, '-');
        $unsigned = ltrim($number, '+-');
        [$integer, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($fraction, '0');
        $canonical = $fraction === '' ? $integer : $integer.'.'.$fraction;

        return $canonical === '0' ? '0' : ($negative ? '-'.$canonical : $canonical);
    }
}
