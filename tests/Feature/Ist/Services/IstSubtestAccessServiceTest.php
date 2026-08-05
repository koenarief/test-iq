<?php

namespace Tests\Feature\Ist\Services;

use App\Enums\Ist\IstAccessDestination;
use App\Enums\Ist\IstSubtestPhase;
use App\Exceptions\Ist\InvalidIstSubtestAccessStateException;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestQuestion;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstSubtestAccessService;
use App\Services\Ist\IstTestLifecycleService;
use App\Support\Ist\IstAnswerType;
use Carbon\CarbonImmutable;

class IstSubtestAccessServiceTest extends IstDatabaseTestCase
{
    private IstSubtestAccessService $accessService;

    private IstTestLifecycleService $lifecycleService;

    private CarbonImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accessService = app(IstSubtestAccessService::class);
        $this->lifecycleService = app(IstTestLifecycleService::class);
        $this->now = CarbonImmutable::parse('2026-08-05 10:00:00', 'UTC');
    }

    public function test_current_instruction_is_canonical_even_when_snapshot_is_incomplete(): void
    {
        [$test, $runtime] = $this->runtime('SE', snapshotComplete: false);

        $decision = $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            $runtime,
            $this->now,
        );

        $this->assertSame(IstAccessDestination::INSTRUCTION, $decision->destination);
        $this->assertSame(IstSubtestPhase::INSTRUCTION, $decision->phase);
        $this->assertTrue($decision->requestedAccessAllowed);
        $this->assertFalse($decision->snapshotComplete);
        $this->assertFalse($decision->answeringAllowed);
        $this->assertNull($decision->phaseEndsAt);
        $this->assertSame(0, $decision->remainingSeconds);
    }

    public function test_current_answering_is_canonical_work_with_server_remaining_time(): void
    {
        [$test, $runtime] = $this->runtime('SE');
        $this->setAnsweringState($test, $runtime, 240);

        $decision = $this->accessService->decide(
            $test,
            IstAccessDestination::WORK,
            $runtime,
            $this->now->addSeconds(60),
        );

        $this->assertSame(IstAccessDestination::WORK, $decision->destination);
        $this->assertSame(IstSubtestPhase::ANSWERING, $decision->phase);
        $this->assertTrue($decision->requestedAccessAllowed);
        $this->assertTrue($decision->answeringAllowed);
        $this->assertTrue($decision->snapshotComplete);
        $this->assertSame(180, $decision->remainingSeconds);
        $this->assertSame($this->now->addSeconds(240)->toISOString(), $decision->phaseEndsAt->toISOString());
    }

    public function test_me_memorization_and_exact_boundary_resolve_to_the_correct_destination(): void
    {
        [$test, $runtime] = $this->runtime('ME');
        $this->setMeState($test, $runtime);

        $memorizing = $this->accessService->decide(
            $test,
            IstAccessDestination::MEMORIZATION,
            $runtime,
            $this->now->addSeconds(119),
        );
        $answering = $this->accessService->decide(
            $test,
            IstAccessDestination::WORK,
            $runtime,
            $this->now->addSeconds(120),
        );

        $this->assertSame(IstAccessDestination::MEMORIZATION, $memorizing->destination);
        $this->assertSame(IstSubtestPhase::MEMORIZING, $memorizing->phase);
        $this->assertSame(1, $memorizing->remainingSeconds);
        $this->assertFalse($memorizing->answeringAllowed);
        $this->assertSame(IstAccessDestination::WORK, $answering->destination);
        $this->assertSame(IstSubtestPhase::ANSWERING, $answering->phase);
        $this->assertSame(240, $answering->remainingSeconds);
        $this->assertTrue($answering->answeringAllowed);
    }

    public function test_exact_answering_deadline_is_expired_and_cannot_answer(): void
    {
        [$test, $runtime] = $this->runtime('SE');
        $this->setAnsweringState($test, $runtime, 240);

        $decision = $this->accessService->decide(
            $test,
            IstAccessDestination::WORK,
            $runtime,
            $this->now->addSeconds(240),
        );

        $this->assertSame(IstAccessDestination::EXPIRED, $decision->destination);
        $this->assertSame(IstSubtestPhase::TIMED_OUT, $decision->phase);
        $this->assertFalse($decision->requestedAccessAllowed);
        $this->assertFalse($decision->answeringAllowed);
        $this->assertSame(0, $decision->remainingSeconds);
    }

    public function test_old_and_future_subtests_are_redirected_to_the_current_state(): void
    {
        [$test, $se] = $this->runtime('SE');
        $wa = $test->subtests()->where('sequence', 2)->firstOrFail();

        $future = $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            $wa,
            $this->now,
        );

        $this->assertFalse($future->requestedAccessAllowed);
        $this->assertSame($se->id, $future->canonicalTestSubtestId);

        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
            'current_subtest_sequence' => 2,
        ]);
        $wa->update(['status' => IstTestSubtest::STATUS_INSTRUCTION]);

        $old = $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            $se,
            $this->now,
        );

        $this->assertFalse($old->requestedAccessAllowed);
        $this->assertSame($wa->id, $old->canonicalTestSubtestId);
        $this->assertSame(IstAccessDestination::INSTRUCTION, $old->destination);
    }

    public function test_wrong_page_for_current_phase_is_not_allowed(): void
    {
        [$test, $runtime] = $this->runtime('SE');

        $workDuringInstruction = $this->accessService->decide(
            $test,
            IstAccessDestination::WORK,
            $runtime,
            $this->now,
        );
        $this->assertFalse($workDuringInstruction->requestedAccessAllowed);
        $this->assertSame(IstAccessDestination::INSTRUCTION, $workDuringInstruction->destination);

        $this->setAnsweringState($test, $runtime, 240);
        $instructionDuringWork = $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            $runtime,
            $this->now,
        );
        $this->assertFalse($instructionDuringWork->requestedAccessAllowed);
        $this->assertSame(IstAccessDestination::WORK, $instructionDuringWork->destination);
    }

    public function test_result_is_denied_before_completion_and_allowed_after_completion(): void
    {
        [$test, $runtime] = $this->runtime('SE');

        $before = $this->accessService->decide(
            $test,
            IstAccessDestination::RESULT,
            null,
            $this->now,
        );

        $this->assertFalse($before->requestedAccessAllowed);
        $this->assertSame(IstAccessDestination::INSTRUCTION, $before->destination);
        $this->assertSame($runtime->id, $before->canonicalTestSubtestId);

        $test->update([
            'status' => IstTest::STATUS_COMPLETED,
            'started_at' => $this->now->subHour(),
            'finished_at' => $this->now,
        ]);

        $after = $this->accessService->decide(
            $test,
            IstAccessDestination::RESULT,
            null,
            $this->now,
        );

        $this->assertTrue($after->requestedAccessAllowed);
        $this->assertSame(IstAccessDestination::RESULT, $after->destination);
        $this->assertNull($after->canonicalTestSubtestId);
    }

    public function test_cancelled_test_is_unavailable(): void
    {
        [$test] = $this->runtime('SE');
        $test->update(['status' => IstTest::STATUS_CANCELLED]);

        $decision = $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            null,
            $this->now,
        );

        $this->assertSame(IstAccessDestination::UNAVAILABLE, $decision->destination);
        $this->assertFalse($decision->requestedAccessAllowed);
        $this->assertFalse($decision->answeringAllowed);
    }

    public function test_requested_runtime_from_another_test_is_not_allowed(): void
    {
        [$test, $runtime] = $this->runtime('SE');
        [, $otherRuntime] = $this->runtime('SE');

        $decision = $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            $otherRuntime,
            $this->now,
        );

        $this->assertFalse($decision->requestedAccessAllowed);
        $this->assertSame($runtime->id, $decision->canonicalTestSubtestId);
    }

    public function test_active_state_with_incomplete_snapshot_is_rejected(): void
    {
        [$test, $runtime] = $this->runtime('SE', snapshotComplete: false);
        $this->setAnsweringState($test, $runtime, 240);

        $this->expectException(InvalidIstSubtestAccessStateException::class);
        $this->accessService->decide(
            $test,
            IstAccessDestination::WORK,
            $runtime,
            $this->now,
        );
    }

    public function test_access_decision_does_not_mutate_database_state(): void
    {
        [$test, $runtime] = $this->runtime('SE');
        $beforeTest = $test->fresh()->getAttributes();
        $beforeRuntime = $runtime->fresh()->getAttributes();
        $beforeSnapshotCount = IstTestQuestion::query()->count();

        $this->accessService->decide(
            $test,
            IstAccessDestination::INSTRUCTION,
            $runtime,
            $this->now,
        );

        $this->assertSame($beforeTest, $test->fresh()->getAttributes());
        $this->assertSame($beforeRuntime, $runtime->fresh()->getAttributes());
        $this->assertSame($beforeSnapshotCount, IstTestQuestion::query()->count());
    }

    private function runtime(string $code, bool $snapshotComplete = true): array
    {
        $result = $this->lifecycleService->create([
            'participant_name' => "Access {$code}",
            'age' => 25,
            'gender' => 'P',
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

        $runtime->update(['question_count' => 1]);

        if ($snapshotComplete) {
            IstTestQuestion::create([
                'ist_test_subtest_id' => $runtime->id,
                'source_question_id' => null,
                'display_order' => 1,
                'answer_type' => IstAnswerType::SINGLE_CHOICE,
                'question_snapshot' => ['prompt' => 'Access fixture'],
                'options_snapshot' => [],
                'answer_key_snapshot' => ['scores' => []],
                'max_score' => 1,
            ]);
        }

        return [$test->fresh(), $runtime->fresh()];
    }

    private function setAnsweringState(
        IstTest $test,
        IstTestSubtest $runtime,
        int $durationSeconds,
    ): void {
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now,
        ]);
        $runtime->update([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'started_at' => $this->now,
            'answering_started_at' => $this->now,
            'answering_ends_at' => $this->now->addSeconds($durationSeconds),
        ]);
    }

    private function setMeState(IstTest $test, IstTestSubtest $runtime): void
    {
        $test->update([
            'status' => IstTest::STATUS_IN_PROGRESS,
            'started_at' => $this->now->subHour(),
        ]);
        $runtime->update([
            'status' => IstTestSubtest::STATUS_MEMORIZING,
            'started_at' => $this->now,
            'memorization_started_at' => $this->now,
            'memorization_ends_at' => $this->now->addSeconds(120),
            'answering_started_at' => $this->now->addSeconds(120),
            'answering_ends_at' => $this->now->addSeconds(360),
        ]);
    }
}
