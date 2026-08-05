<?php

namespace Tests\Feature\Ist\Services;

use App\Enums\Ist\IstFinalizationReason;
use App\Exceptions\Ist\InvalidIstFinalizationException;
use App\Exceptions\Ist\IstAnswerRevisionConflictException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstAutosaveService;
use App\Services\Ist\IstSubtestFinalizationService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;

class IstSubtestFinalizationServiceTest extends IstDatabaseTestCase
{
    private IstSubtestFinalizationService $service;

    private IstAutosaveService $autosave;

    private IstTestLifecycleService $lifecycle;

    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(IstSubtestFinalizationService::class);
        $this->autosave = app(IstAutosaveService::class);
        $this->lifecycle = app(IstTestLifecycleService::class);
        $this->now = CarbonImmutable::parse('2026-08-05 10:00:00', 'UTC');
    }

    public function test_submitted_scores_every_type_creates_blanks_locks_and_advances_once(): void
    {
        [$test, $runtime, $questions] = $this->scoringRuntime();

        $result = $this->service->finalize(
            $runtime,
            IstFinalizationReason::SUBMITTED,
            $this->now,
            [
                $this->choiceChange($questions[0], 'B', 1),
                $this->choiceChange($questions[1], 'C', 1),
                $this->numericChange($questions[2], '10.00', 1),
            ],
        );

        $runtime->refresh();
        $test->refresh();
        $next = $test->subtests()->where('sequence', 2)->firstOrFail();

        $this->assertFalse($result->alreadyFinalized);
        $this->assertSame(IstFinalizationReason::SUBMITTED, $result->persistedReason);
        $this->assertSame(IstTestSubtest::STATUS_COMPLETED, $runtime->status);
        $this->assertSame('submitted', $runtime->finalized_reason);
        $this->assertNotNull($runtime->locked_at);
        $this->assertSame('4.0000', $runtime->awarded_score);
        $this->assertSame('7.0000', $runtime->max_score);
        $this->assertSame(2, $runtime->correct_count);
        $this->assertSame(1, $runtime->partial_count);
        $this->assertSame(0, $runtime->wrong_count);
        $this->assertSame(1, $runtime->blank_count);
        $this->assertSame('57.143', $runtime->percentage);
        $this->assertSame(4, IstAnswer::whereIn('ist_test_question_id', collect($questions)->pluck('id'))->count());
        $this->assertDatabaseHas('ist_answers', [
            'ist_test_question_id' => $questions[3]->id,
            'selected_option_key' => null,
            'numeric_answer' => null,
            'awarded_score' => '0.0000',
            'outcome' => IstAnswer::OUTCOME_BLANK,
        ]);
        $this->assertSame(2, $test->current_subtest_sequence);
        $this->assertSame(IstTestSubtest::STATUS_INSTRUCTION, $next->status);
        $this->assertNull($next->started_at);
        $this->assertNull($next->answering_ends_at);
        $this->assertNull($test->total_internal_score);
    }

    public function test_submit_and_timeout_obey_exact_deadline_boundaries(): void
    {
        [, $runtime] = $this->scoringRuntime();

        try {
            $this->service->finalize(
                $runtime,
                IstFinalizationReason::TIMEOUT,
                $this->now,
            );
            $this->fail('Timeout before deadline was accepted.');
        } catch (InvalidIstFinalizationException) {
            $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
        }

        try {
            $this->service->finalize(
                $runtime,
                IstFinalizationReason::SUBMITTED,
                $this->now->addMinute(),
            );
            $this->fail('Submitted finalization at deadline was accepted.');
        } catch (InvalidIstFinalizationException) {
            $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
        }

        $result = $this->service->finalize(
            $runtime,
            IstFinalizationReason::TIMEOUT,
            $this->now->addMinute(),
        );

        $this->assertSame(IstTestSubtest::STATUS_TIMED_OUT, $result->status);
        $this->assertSame(4, $result->blankCount);
        $this->assertSame(4, IstAnswer::query()
            ->whereIn('ist_test_question_id', $runtime->testQuestions()->pluck('id'))
            ->where('outcome', IstAnswer::OUTCOME_BLANK)
            ->count());
    }

    public function test_terminal_calls_are_idempotent_even_when_requested_reason_differs(): void
    {
        [$test, $runtime] = $this->scoringRuntime();
        $first = $this->service->finalize(
            $runtime,
            IstFinalizationReason::SUBMITTED,
            $this->now,
        );
        $sequence = $test->fresh()->current_subtest_sequence;
        $answerCount = IstAnswer::query()->count();

        $same = $this->service->finalize(
            $runtime,
            IstFinalizationReason::SUBMITTED,
            $this->now->addSecond(),
        );
        $different = $this->service->finalize(
            $runtime,
            IstFinalizationReason::TIMEOUT,
            $this->now->addMinute(),
        );

        $this->assertFalse($first->alreadyFinalized);
        $this->assertTrue($same->alreadyFinalized);
        $this->assertTrue($different->alreadyFinalized);
        $this->assertSame(IstFinalizationReason::SUBMITTED, $same->persistedReason);
        $this->assertSame(IstFinalizationReason::SUBMITTED, $different->persistedReason);
        $this->assertSame($sequence, $test->fresh()->current_subtest_sequence);
        $this->assertSame($answerCount, IstAnswer::query()->count());

        [$timeoutTest, $timeoutRuntime] = $this->scoringRuntime('Timeout idempotent');
        $timedOut = $this->service->finalize(
            $timeoutRuntime,
            IstFinalizationReason::TIMEOUT,
            $this->now->addMinute(),
        );
        $repeated = $this->service->finalize(
            $timeoutRuntime,
            IstFinalizationReason::TIMEOUT,
            $this->now->addMinutes(2),
        );
        $mismatched = $this->service->finalize(
            $timeoutRuntime,
            IstFinalizationReason::SUBMITTED,
            $this->now,
        );

        $this->assertFalse($timedOut->alreadyFinalized);
        $this->assertTrue($repeated->alreadyFinalized);
        $this->assertTrue($mismatched->alreadyFinalized);
        $this->assertSame(IstFinalizationReason::TIMEOUT, $mismatched->persistedReason);
        $this->assertSame(2, $timeoutTest->fresh()->current_subtest_sequence);
    }

    public function test_final_batch_uses_stale_revision_protection_without_overwriting(): void
    {
        [, $runtime, $questions] = $this->scoringRuntime();
        $this->autosave->save(
            $runtime,
            [$this->choiceChange($questions[0], 'B', 3)],
            $this->now,
        );

        $result = $this->service->finalize(
            $runtime,
            IstFinalizationReason::SUBMITTED,
            $this->now->addSecond(),
            [$this->choiceChange($questions[0], 'A', 2)],
        );

        $answer = IstAnswer::where('ist_test_question_id', $questions[0]->id)->firstOrFail();
        $this->assertSame('B', $answer->selected_option_key);
        $this->assertSame(3, $answer->client_revision);
        $this->assertSame(IstAnswer::OUTCOME_CORRECT, $answer->outcome);
        $this->assertSame(1, $result->correctCount);
    }

    public function test_revision_conflict_in_final_batch_rolls_back_finalization(): void
    {
        [$test, $runtime, $questions] = $this->scoringRuntime();
        $this->autosave->save(
            $runtime,
            [$this->choiceChange($questions[0], 'B', 3)],
            $this->now,
        );

        try {
            $this->service->finalize(
                $runtime,
                IstFinalizationReason::SUBMITTED,
                $this->now->addSecond(),
                [$this->choiceChange($questions[0], 'A', 3)],
            );
            $this->fail('Revision conflict did not abort finalization.');
        } catch (IstAnswerRevisionConflictException) {
            $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
            $this->assertNull($runtime->fresh()->locked_at);
            $this->assertSame(1, $test->fresh()->current_subtest_sequence);
            $this->assertSame('B', IstAnswer::where('ist_test_question_id', $questions[0]->id)->value('selected_option_key'));
        }
    }

    public function test_timeout_rejects_final_answers_without_changing_state(): void
    {
        [$test, $runtime, $questions] = $this->scoringRuntime();

        try {
            $this->service->finalize(
                $runtime,
                IstFinalizationReason::TIMEOUT,
                $this->now->addMinute(),
                [$this->choiceChange($questions[0], 'B', 1)],
            );
            $this->fail('Timeout final answers were accepted.');
        } catch (InvalidIstFinalizationException) {
            $this->assertDatabaseCount('ist_answers', 0);
            $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
            $this->assertSame(1, $test->fresh()->current_subtest_sequence);
        }
    }

    public function test_scoring_failure_rolls_back_answers_runtime_and_sequence(): void
    {
        [$test, $runtime] = $this->scoringRuntime();
        $runtime->testQuestions()->delete();
        $runtime->update(['question_count' => 1]);
        IstTestQuestion::create([
            'ist_test_subtest_id' => $runtime->id,
            'source_question_id' => null,
            'display_order' => 1,
            'answer_type' => IstAnswerType::NUMERIC,
            'question_snapshot' => ['prompt' => 'Broken numeric'],
            'options_snapshot' => [],
            'answer_key_snapshot' => [],
            'max_score' => 1,
        ]);

        try {
            $this->service->finalize($runtime, IstFinalizationReason::SUBMITTED, $this->now);
            $this->fail('Invalid scoring snapshot was accepted.');
        } catch (InvalidIstFinalizationException) {
            $this->assertDatabaseCount('ist_answers', 0);
            $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $runtime->fresh()->status);
            $this->assertSame(1, $test->fresh()->current_subtest_sequence);
        }
    }

    public function test_finalizing_me_completes_overall_test_with_mean_percentage(): void
    {
        $creation = $this->lifecycle->create([
            'participant_name' => 'ME Final',
            'age' => 30,
            'gender' => 'P',
        ]);
        $creation->takeRawAccessToken();
        $test = $creation->test;
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subMinutes(45),
            'current_subtest_sequence' => 9,
        ]);

        foreach ($test->subtests()->where('sequence', '<', 9)->get() as $earlier) {
            $percentage = $earlier->sequence * 10;
            $earlier->update([
                'status' => IstTestSubtest::STATUS_COMPLETED,
                'locked_at' => $this->now->subMinute(),
                'finalized_reason' => IstFinalizationReason::SUBMITTED->value,
                'percentage' => $percentage,
            ]);
        }

        $runtime = $test->subtests()->where('sequence', 9)->firstOrFail();
        $runtime->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'question_count' => 1,
            'started_at' => $this->now->subMinutes(4),
            'memorization_started_at' => $this->now->subMinutes(6),
            'memorization_ends_at' => $this->now->subMinutes(4),
            'answering_started_at' => $this->now->subMinutes(4),
            'answering_ends_at' => $this->now->addMinute(),
        ]);
        $question = $this->createChoiceQuestion($runtime, 1, IstAnswerType::SINGLE_CHOICE, 1);

        $result = $this->service->finalize(
            $runtime,
            IstFinalizationReason::SUBMITTED,
            $this->now,
            [$this->choiceChange($question, 'B', 1)],
        );

        $test->refresh();
        $this->assertTrue($result->overallCompleted);
        $this->assertSame(IstTest::STATUS_COMPLETED, $test->status);
        $this->assertSame(9, $test->current_subtest_sequence);
        $this->assertSame('51.111', $test->total_internal_score);
        $this->assertSame(51.111, $result->totalInternalScore);
        $this->assertNotNull($test->finished_at);
        $this->assertNull($test->subtests()->where('sequence', 10)->first());
    }

    private function scoringRuntime(string $participant = 'Finalization'): array
    {
        $creation = $this->lifecycle->create([
            'participant_name' => $participant,
            'age' => 28,
            'gender' => 'L',
        ]);
        $creation->takeRawAccessToken();
        $test = $creation->test;
        $runtime = $test->subtests()->where('sequence', 1)->firstOrFail();
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 1,
        ]);
        $runtime->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'question_count' => 4,
            'started_at' => $this->now->subMinute(),
            'answering_started_at' => $this->now->subMinute(),
            'answering_ends_at' => $this->now->addMinute(),
        ]);

        $questions = [
            $this->createChoiceQuestion($runtime, 1, IstAnswerType::SINGLE_CHOICE, 1),
            $this->createChoiceQuestion($runtime, 2, IstAnswerType::SINGLE_CHOICE_WEIGHTED, 4),
            IstTestQuestion::create([
                'ist_test_subtest_id' => $runtime->id,
                'source_question_id' => null,
                'display_order' => 3,
                'answer_type' => IstAnswerType::NUMERIC,
                'question_snapshot' => ['prompt' => 'Numeric'],
                'options_snapshot' => [],
                'answer_key_snapshot' => ['numeric_answer' => '10.000000'],
                'max_score' => 1,
            ]),
            $this->createChoiceQuestion($runtime, 4, IstAnswerType::IMAGE_CHOICE, 1),
        ];

        return [$test->fresh(), $runtime->fresh(), $questions];
    }

    private function createChoiceQuestion(
        IstTestSubtest $runtime,
        int $order,
        string $answerType,
        int $maxScore,
    ): IstTestQuestion {
        $keys = ['A', 'B', 'C', 'D', 'E'];
        $answerKey = $answerType === IstAnswerType::SINGLE_CHOICE_WEIGHTED
            ? ['scores' => ['A' => 0, 'B' => 4, 'C' => 2, 'D' => 1, 'E' => 3]]
            : ['correct_option_key' => 'B'];

        return IstTestQuestion::create([
            'ist_test_subtest_id' => $runtime->id,
            'source_question_id' => null,
            'display_order' => $order,
            'answer_type' => $answerType,
            'question_snapshot' => ['prompt' => "Choice {$order}"],
            'options_snapshot' => array_map(
                static fn (string $key, int $index): array => [
                    'option_key' => $key,
                    'display_order' => $index + 1,
                ],
                $keys,
                array_keys($keys),
            ),
            'answer_key_snapshot' => $answerKey,
            'max_score' => $maxScore,
        ]);
    }

    private function choiceChange(IstTestQuestion $question, ?string $option, int $revision): array
    {
        return [
            'ist_test_question_id' => $question->id,
            'selected_option_key' => $option,
            'client_revision' => $revision,
        ];
    }

    private function numericChange(IstTestQuestion $question, ?string $answer, int $revision): array
    {
        return [
            'ist_test_question_id' => $question->id,
            'numeric_answer' => $answer,
            'client_revision' => $revision,
        ];
    }
}
