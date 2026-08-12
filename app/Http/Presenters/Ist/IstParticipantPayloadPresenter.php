<?php

namespace App\Http\Presenters\Ist;

use App\Data\Ist\IstResultData;
use App\Data\Ist\IstSubtestAccessDecision;
use App\Data\Ist\IstSubtestResultData;
use App\Enums\Ist\IstAccessDestination;
use App\Models\Ist\IstQuestion;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Support\Ist\IstMeRuntimeContent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class IstParticipantPayloadPresenter
{
    public function __construct(
        private readonly IstMeRuntimeContent $meRuntimeContent,
    ) {}

    public function instruction(
        IstTest $test,
        IstTestSubtest $runtime,
        bool $snapshotComplete,
        bool $exampleCompleted = true,
    ): array {
        $runtime->loadMissing('subtest');
        $requiresExampleCompletion = $runtime->subtest->code === 'ME';
        $meRuntime = $requiresExampleCompletion
            ? $this->meRuntimeSnapshot($runtime)
            : null;

        $examples = [];

        if (! $requiresExampleCompletion || $meRuntime !== null) {
            $examples = IstQuestion::query()
                ->where('ist_subtest_id', $runtime->ist_subtest_id)
                ->where('kind', IstQuestion::KIND_EXAMPLE)
                ->where('is_active', true)
                ->with(['options' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('display_order')])
                ->orderBy('display_order')
                ->get()
                ->map(fn (IstQuestion $question): array => [
                    'displayOrder' => $question->display_order,
                    'answerType' => $question->answer_type,
                    'prompt' => $question->prompt,
                    'image' => $this->media(
                        $question->image_disk,
                        $question->image_path,
                        $question->image_alt,
                    ),
                    'options' => $question->options->map(fn ($option): array => [
                        'optionKey' => $option->option_key,
                        'text' => $option->option_text,
                        'displayOrder' => $option->display_order,
                        'image' => $this->media(
                            $option->image_disk,
                            $option->image_path,
                            $option->image_alt,
                        ),
                    ])->values()->all(),
                    'explanation' => $requiresExampleCompletion && ! $exampleCompleted
                        ? null
                        : $question->example_explanation,
                ])
                ->values()
                ->all();
        }

        return [
            'testPublicId' => $test->public_id,
            'subtest' => [
                'code' => $runtime->subtest->code,
                'name' => $runtime->subtest->name,
                'sequence' => $runtime->sequence,
                'questionCount' => $runtime->question_count,
                'instructionContent' => $requiresExampleCompletion
                    ? ($meRuntime['instructionContent'] ?? null)
                    : $runtime->subtest->instruction_content,
                'durationSeconds' => $runtime->subtest->duration_seconds,
                'memorizationSeconds' => $runtime->subtest->memorization_seconds,
                'answeringSeconds' => $runtime->subtest->answering_seconds,
            ],
            'examples' => $examples,
            'snapshotComplete' => $snapshotComplete,
            'requiresExampleCompletion' => $requiresExampleCompletion,
            'exampleCompleted' => ! $requiresExampleCompletion || $exampleCompleted,
            'exampleCompletionUrl' => $requiresExampleCompletion
                ? route('ist.subtests.example.complete', [
                    'test' => $test->public_id,
                    'subtest' => 'ME',
                ])
                : null,
            'canStart' => $snapshotComplete
                && (! $requiresExampleCompletion || ($exampleCompleted && $meRuntime !== null)),
            'startUrl' => route('ist.subtests.start', [
                'test' => $test->public_id,
                'subtest' => $runtime->subtest->code,
            ]),
        ];
    }

    public function work(
        IstTest $test,
        IstTestSubtest $runtime,
        IstSubtestAccessDecision $decision,
        CarbonInterface $serverTime,
    ): array {
        $runtime->loadMissing('subtest');
        $meRuntime = $runtime->subtest->code === 'ME'
            ? $this->meRuntimeSnapshot($runtime)
            : null;
        $mode = match ($decision->destination) {
            IstAccessDestination::MEMORIZATION => 'memorization',
            IstAccessDestination::EXPIRED => 'expired',
            default => 'answering',
        };

        $questions = [];

        if ($mode === 'answering') {
            $questions = $runtime->testQuestions()
                ->with('answer')
                ->orderBy('display_order')
                ->get()
                ->map(fn (IstTestQuestion $question): array => [
                    'id' => $question->id,
                    'displayOrder' => $question->display_order,
                    'answerType' => $question->answer_type,
                    'prompt' => $question->question_snapshot['prompt'] ?? null,
                    'image' => $this->media(
                        $question->question_snapshot['image_disk'] ?? null,
                        $question->question_snapshot['image_path'] ?? null,
                        $question->question_snapshot['image_alt'] ?? null,
                    ),
                    'options' => array_map(fn (array $option): array => [
                        'optionKey' => $option['option_key'] ?? null,
                        'text' => $option['option_text'] ?? null,
                        'displayOrder' => $option['display_order'] ?? null,
                        'image' => $this->media(
                            $option['image_disk'] ?? null,
                            $option['image_path'] ?? null,
                            $option['image_alt'] ?? null,
                        ),
                    ], $question->options_snapshot ?? []),
                    'savedAnswer' => $question->answer === null ? null : [
                        'selectedOptionKey' => $question->answer->selected_option_key,
                        'numericAnswer' => $question->answer->numeric_answer,
                        'clientRevision' => $question->answer->client_revision,
                    ],
                ])
                ->values()
                ->all();
        }

        return [
            'testPublicId' => $test->public_id,
            'mode' => $mode,
            'subtest' => [
                'code' => $runtime->subtest->code,
                'name' => $runtime->subtest->name,
                'sequence' => $runtime->sequence,
            ],
            'memorizationGroups' => $mode === 'memorization'
                ? ($meRuntime['groups'] ?? [])
                : [],
            'questions' => $questions,
            'serverTime' => $serverTime->toISOString(),
            'phaseEndsAt' => $decision->phaseEndsAt?->toISOString(),
            'remainingSeconds' => $decision->remainingSeconds,
            'autosaveUrl' => route('ist.subtests.answers.update', [
                'test' => $test->public_id,
                'subtest' => $runtime->subtest->code,
            ]),
            'finishUrl' => route('ist.subtests.finish', [
                'test' => $test->public_id,
                'subtest' => $runtime->subtest->code,
            ]),
        ];
    }

    public function result(IstResultData $result): array
    {
        return [
            'participant' => [
                'name' => $result->participantName,
                'age' => $result->age,
                'ageGroup' => $result->ageGroup,
                'gender' => $result->gender,
            ],

            'startedAt' => $result->startedAt->toISOString(),
            'finishedAt' => $result->finishedAt->toISOString(),
            'durationSeconds' => $result->durationSeconds,

            /*
            |--------------------------------------------------------------------------
            | 9 Subtests
            |--------------------------------------------------------------------------
            */
            'subtests' => array_map(
                static fn (IstSubtestResultData $subtest): array => [
                    'code' => $subtest->code,
                    'name' => $subtest->name,
                    'sequence' => $subtest->sequence,
                    'awardedScore' => $subtest->awardedScore,
                    'maxScore' => $subtest->maxScore,
                    'correctCount' => $subtest->correctCount,
                    'partialCount' => $subtest->partialCount,
                    'wrongCount' => $subtest->wrongCount,
                    'blankCount' => $subtest->blankCount,
                    'percentage' => $subtest->percentage,
                ],
                $result->subtests,
            ),

            /*
            |--------------------------------------------------------------------------
            | 9 Subtest Chart
            |--------------------------------------------------------------------------
            */
            'graphPoints' => $result->graphPoints,

            /*
            |--------------------------------------------------------------------------
            | 4 Cognitive Areas
            |--------------------------------------------------------------------------
            */
            'areaScores' => $result->areaScores,
            'areaGraphPoints' => $result->areaGraphPoints,

            /*
            |--------------------------------------------------------------------------
            | Overall Cognitive Performance
            |--------------------------------------------------------------------------
            */
            'totalInternalScore' => $result->totalInternalScore,
            'performanceCategory' => $result->performanceCategory,
            'performanceBenchmark' => $result->performanceBenchmark,

            /*
            |--------------------------------------------------------------------------
            | Profile Interpretation
            |--------------------------------------------------------------------------
            */
            'strongestAreas' => $result->strongestAreas,
            'developmentAreas' => $result->developmentAreas,
            'profileSpread' => $result->profileSpread,
            'profileBalanceLabel' => $result->profileBalanceLabel,
        ];
    }

    private function media(?string $disk, ?string $path, ?string $alt): array
    {
        if ($path === null || trim($path) === '') {
            return ['url' => null, 'alt' => $alt];
        }

        $path = trim($path);

        if (str_starts_with($path, '/')
            || str_contains($path, '..')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1) {
            return ['url' => null, 'alt' => $alt];
        }

        try {
            $url = Storage::disk($disk ?: config('filesystems.default'))->url($path);
        } catch (Throwable) {
            $url = null;
        }

        return ['url' => $url, 'alt' => $alt];
    }

    private function meRuntimeSnapshot(IstTestSubtest $runtime): ?array
    {
        $question = $runtime->testQuestions()
            ->orderBy('display_order')
            ->first(['question_snapshot']);

        return $this->meRuntimeContent->fromQuestionSnapshot(
            $question?->question_snapshot,
        );
    }
}
