<?php

namespace Tests\Feature\Ist\Services;

use App\Exceptions\Ist\IncompleteIstSnapshotException;
use App\Exceptions\Ist\InvalidIstSubtestStartException;
use App\Models\Ist\IstAnswer;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstSubtestStartService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;

class IstSubtestStartServiceTest extends IstDatabaseTestCase
{
    private IstSubtestStartService $startService;

    private IstTestLifecycleService $lifecycleService;

    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->startService = app(IstSubtestStartService::class);
        $this->lifecycleService = app(IstTestLifecycleService::class);
        $this->now = CarbonImmutable::parse('2026-08-05 10:00:00', 'UTC');
    }

    public function test_starting_se_sets_runtime_deadline_and_overall_start_state(): void
    {
        [$test, $runtime] = $this->preparedRuntime('SE');

        $started = $this->startService->start($runtime, $this->now);
        $test->refresh();

        $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $started->status);
        $this->assertSame($this->now->toISOString(), $started->started_at->toISOString());
        $this->assertSame($this->now->toISOString(), $started->answering_started_at->toISOString());
        $this->assertSame($this->now->addSeconds(240)->toISOString(), $started->answering_ends_at->toISOString());
        $this->assertNull($started->memorization_started_at);
        $this->assertNull($started->memorization_ends_at);
        $this->assertSame(IstTest::STATUS_IN_PROGRESS, $test->status);
        $this->assertSame($this->now->toISOString(), $test->started_at->toISOString());
    }

    public function test_second_se_start_keeps_all_original_deadlines_and_overall_start_time(): void
    {
        [$test, $runtime] = $this->preparedRuntime('SE');
        $first = $this->startService->start($runtime, $this->now);
        $overallStartedAt = $test->fresh()->started_at->toISOString();

        $second = $this->startService->start($first, $this->now->addMinutes(2));
        $test->refresh();

        $this->assertSame($first->started_at->toISOString(), $second->started_at->toISOString());
        $this->assertSame($first->answering_started_at->toISOString(), $second->answering_started_at->toISOString());
        $this->assertSame($first->answering_ends_at->toISOString(), $second->answering_ends_at->toISOString());
        $this->assertSame($overallStartedAt, $test->started_at->toISOString());
    }

    public function test_starting_a_later_subtest_does_not_change_overall_start_time_or_sequence(): void
    {
        [$test, $runtime] = $this->preparedRuntime('WA');
        $overallStartedAt = $test->started_at->toISOString();

        $started = $this->startService->start($runtime, $this->now);
        $test->refresh();

        $this->assertSame(IstTestSubtest::STATUS_ANSWERING, $started->status);
        $this->assertSame($this->now->addSeconds(240)->toISOString(), $started->answering_ends_at->toISOString());
        $this->assertSame(IstTest::STATUS_IN_PROGRESS, $test->status);
        $this->assertSame($overallStartedAt, $test->started_at->toISOString());
        $this->assertSame(2, $test->current_subtest_sequence);
    }

    public function test_me_single_start_sets_both_phases_and_is_idempotent(): void
    {
        [$test, $runtime] = $this->preparedRuntime('ME');
        $overallStartedAt = $test->started_at->toISOString();

        $first = $this->startService->start($runtime, $this->now);
        $second = $this->startService->start($first, $this->now->addSeconds(180));
        $test->refresh();

        $this->assertSame(IstTestSubtest::STATUS_MEMORIZING, $first->status);
        $this->assertSame($this->now->toISOString(), $first->memorization_started_at->toISOString());
        $this->assertSame($this->now->addSeconds(120)->toISOString(), $first->memorization_ends_at->toISOString());
        $this->assertSame($this->now->addSeconds(120)->toISOString(), $first->answering_started_at->toISOString());
        $this->assertSame($this->now->addSeconds(360)->toISOString(), $first->answering_ends_at->toISOString());
        $this->assertSame($first->started_at->toISOString(), $second->started_at->toISOString());
        $this->assertSame($first->memorization_ends_at->toISOString(), $second->memorization_ends_at->toISOString());
        $this->assertSame($first->answering_ends_at->toISOString(), $second->answering_ends_at->toISOString());
        $this->assertSame($overallStartedAt, $test->started_at->toISOString());
    }

    public function test_start_rejects_both_missing_and_excess_snapshots(): void
    {
        [, $runtime] = $this->preparedRuntime('SE', snapshotCount: 0, expectedCount: 1);

        try {
            $this->startService->start($runtime, $this->now);
            $this->fail('Start accepted an incomplete snapshot.');
        } catch (IncompleteIstSnapshotException) {
            $this->assertNull($runtime->fresh()->started_at);
        }

        [, $runtimeWithExcess] = $this->preparedRuntime('SE', snapshotCount: 2, expectedCount: 1);

        $this->expectException(IncompleteIstSnapshotException::class);
        $this->startService->start($runtimeWithExcess, $this->now);
    }

    public function test_non_current_and_locked_subtests_are_rejected(): void
    {
        [, $runtime] = $this->preparedRuntime('SE');
        $runtime->test()->update(['current_subtest_sequence' => 2]);

        try {
            $this->startService->start($runtime, $this->now);
            $this->fail('Non-current subtest was started.');
        } catch (InvalidIstSubtestStartException) {
            $this->assertNull($runtime->fresh()->started_at);
        }

        $runtime->test()->update(['current_subtest_sequence' => 1]);
        $runtime->update(['locked_at' => $this->now]);

        $this->expectException(InvalidIstSubtestStartException::class);
        $this->startService->start($runtime->fresh(), $this->now);
    }

    public function test_completed_and_cancelled_tests_are_rejected(): void
    {
        [$completed, $completedRuntime] = $this->preparedRuntime('SE');
        $completed->update([
            'status' => IstTest::STATUS_COMPLETED,
            'started_at' => $this->now->subHour(),
            'finished_at' => $this->now,
        ]);

        try {
            $this->startService->start($completedRuntime, $this->now);
            $this->fail('Completed test was started.');
        } catch (InvalidIstSubtestStartException) {
            $this->assertNull($completedRuntime->fresh()->started_at);
        }

        [$cancelled, $cancelledRuntime] = $this->preparedRuntime('SE');
        $cancelled->update([
            'status' => IstTest::STATUS_CANCELLED,
            'started_at' => $this->now->subHour(),
        ]);

        $this->expectException(InvalidIstSubtestStartException::class);
        $this->startService->start($cancelledRuntime, $this->now);
    }

    public function test_inconsistent_overall_start_states_are_rejected(): void
    {
        [$inProgress, $runtime] = $this->preparedRuntime('SE');
        $inProgress->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => null,
        ]);

        try {
            $this->startService->start($runtime, $this->now);
            $this->fail('In-progress test without started_at was accepted.');
        } catch (InvalidIstSubtestStartException) {
            $this->assertNull($runtime->fresh()->started_at);
        }

        [$draft, $draftRuntime] = $this->preparedRuntime('SE');
        $draft->update([
            'status' => IstTest::STATUS_DRAFT,
            'started_at' => $this->now->subHour(),
        ]);

        $this->expectException(InvalidIstSubtestStartException::class);
        $this->startService->start($draftRuntime, $this->now);
    }

    public function test_start_does_not_create_answers_scores_locks_or_advance_sequence(): void
    {
        [$test, $runtime] = $this->preparedRuntime('SE');

        $started = $this->startService->start($runtime, $this->now);
        $test->refresh();

        $this->assertSame(0, IstAnswer::query()->count());
        $this->assertSame('0.0000', $started->awarded_score);
        $this->assertSame('0.0000', $started->max_score);
        $this->assertSame(0, $started->correct_count);
        $this->assertSame(0, $started->partial_count);
        $this->assertSame(0, $started->wrong_count);
        $this->assertSame(0, $started->blank_count);
        $this->assertNull($started->locked_at);
        $this->assertSame(1, $test->current_subtest_sequence);
    }

    private function preparedRuntime(
        string $code,
        int $snapshotCount = 1,
        int $expectedCount = 1,
    ): array {
        $result = $this->lifecycleService->create([
            'participant_name' => "Start {$code}",
            'age' => 25,
            'gender' => 'L',
        ]);
        $result->takeRawAccessToken();
        $test = $result->test;
        $runtime = $test->subtests()
            ->whereHas('subtest', fn ($query) => $query->where('code', $code))
            ->firstOrFail();

        if ($runtime->sequence > 1) {
            $test->update([
                'status' => IstTest::STATUS_IN_PROGRESS,
                'started_at' => $this->now->subHour(),
                'current_subtest_sequence' => $runtime->sequence,
            ]);
            $runtime->update(['status' => IstTestSubtest::STATUS_INSTRUCTION]);
        }

        $runtime->update(['question_count' => $expectedCount]);

        for ($index = 1; $index <= $snapshotCount; $index++) {
            IstTestQuestion::create([
                'ist_test_subtest_id' => $runtime->id,
                'source_question_id' => null,
                'display_order' => $index,
                'answer_type' => IstAnswerType::SINGLE_CHOICE,
                'question_snapshot' => ['prompt' => "Snapshot {$index}"],
                'options_snapshot' => [],
                'answer_key_snapshot' => ['scores' => []],
                'max_score' => 1,
            ]);
        }

        return [$test->fresh(), $runtime->fresh('subtest')];
    }
}
