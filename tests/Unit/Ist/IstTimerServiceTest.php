<?php

namespace Tests\Unit\Ist;

use App\Enums\Ist\IstSubtestPhase;
use App\Models\Ist\IstTestSubtest;
use App\Services\Ist\IstTimerService;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class IstTimerServiceTest extends TestCase
{
    private IstTimerService $timer;

    private CarbonImmutable $start;

    protected function setUp(): void
    {
        parent::setUp();

        $this->timer = new IstTimerService;
        $this->start = CarbonImmutable::parse('2026-08-05 10:00:00', 'UTC');
    }

    public function test_remaining_seconds_are_calculated_from_the_existing_deadline(): void
    {
        $runtime = $this->normalRuntime();

        $this->assertSame(60, $this->timer->getRemainingSeconds(
            $runtime,
            $this->start->addSeconds(180),
        ));
        $this->assertSame(
            $this->start->addSeconds(240)->toISOString(),
            $this->timer->getPhaseEndsAt($runtime, $this->start)->toISOString(),
        );
    }

    public function test_remaining_seconds_never_become_negative(): void
    {
        $runtime = $this->normalRuntime();

        $this->assertSame(0, $this->timer->getRemainingSeconds(
            $runtime,
            $this->start->addSeconds(300),
        ));
    }

    public function test_refresh_uses_the_same_stored_deadline(): void
    {
        $runtime = $this->normalRuntime();
        $deadlineBefore = $runtime->answering_ends_at->toISOString();

        $this->assertSame(210, $this->timer->getRemainingSeconds(
            $runtime,
            $this->start->addSeconds(30),
        ));
        $this->assertSame(120, $this->timer->getRemainingSeconds(
            $runtime,
            $this->start->addSeconds(120),
        ));
        $this->assertSame($deadlineBefore, $runtime->answering_ends_at->toISOString());
    }

    public function test_exact_deadline_is_expired_but_one_second_before_is_active(): void
    {
        $runtime = $this->normalRuntime();
        $deadline = $this->start->addSeconds(240);

        $this->assertFalse($this->timer->isExpired($runtime, $deadline->subSecond()));
        $this->assertSame(1, $this->timer->getRemainingSeconds($runtime, $deadline->subSecond()));
        $this->assertTrue($this->timer->isExpired($runtime, $deadline));
        $this->assertSame(IstSubtestPhase::TIMED_OUT, $this->timer->getCurrentPhase($runtime, $deadline));
        $this->assertSame(0, $this->timer->getRemainingSeconds($runtime, $deadline));
    }

    public function test_instruction_has_no_deadline(): void
    {
        $runtime = new IstTestSubtest([
            'status' => IstTestSubtest::STATUS_INSTRUCTION,
        ]);

        $this->assertSame(IstSubtestPhase::INSTRUCTION, $this->timer->getCurrentPhase($runtime, $this->start));
        $this->assertNull($this->timer->getPhaseEndsAt($runtime, $this->start));
        $this->assertSame(0, $this->timer->getRemainingSeconds($runtime, $this->start));
    }

    public function test_completed_and_physical_timed_out_statuses_take_precedence(): void
    {
        $completed = $this->normalRuntime();
        $completed->status = IstTestSubtest::STATUS_COMPLETED;
        $timedOut = $this->normalRuntime();
        $timedOut->status = IstTestSubtest::STATUS_TIMED_OUT;

        $this->assertSame(IstSubtestPhase::COMPLETED, $this->timer->getCurrentPhase(
            $completed,
            $this->start->addHour(),
        ));
        $this->assertFalse($this->timer->isExpired($completed, $this->start->addHour()));
        $this->assertSame(IstSubtestPhase::TIMED_OUT, $this->timer->getCurrentPhase($timedOut, $this->start));
        $this->assertTrue($this->timer->isExpired($timedOut, $this->start));
    }

    public function test_me_changes_phase_exactly_at_each_server_deadline(): void
    {
        $runtime = $this->meRuntime();
        $memorizationEnd = $this->start->addSeconds(120);
        $answeringEnd = $this->start->addSeconds(360);

        $this->assertSame(IstSubtestPhase::MEMORIZING, $this->timer->getCurrentPhase(
            $runtime,
            $memorizationEnd->subSecond(),
        ));
        $this->assertSame(IstSubtestPhase::ANSWERING, $this->timer->getCurrentPhase(
            $runtime,
            $memorizationEnd,
        ));
        $this->assertSame(IstSubtestPhase::TIMED_OUT, $this->timer->getCurrentPhase(
            $runtime,
            $answeringEnd,
        ));
        $this->assertTrue($this->timer->isExpired($runtime, $answeringEnd));
    }

    public function test_me_phase_deadline_switches_without_recalculating_timestamps(): void
    {
        $runtime = $this->meRuntime();
        $memorizationDeadline = $runtime->memorization_ends_at->toISOString();
        $answeringDeadline = $runtime->answering_ends_at->toISOString();

        $this->assertSame(
            $memorizationDeadline,
            $this->timer->getPhaseEndsAt($runtime, $this->start->addSeconds(60))->toISOString(),
        );
        $this->assertSame(
            $answeringDeadline,
            $this->timer->getPhaseEndsAt($runtime, $this->start->addSeconds(120))->toISOString(),
        );
        $this->assertSame($memorizationDeadline, $runtime->memorization_ends_at->toISOString());
        $this->assertSame($answeringDeadline, $runtime->answering_ends_at->toISOString());
    }

    private function normalRuntime(): IstTestSubtest
    {
        $runtime = new IstTestSubtest;
        $runtime->setRawAttributes([
            'status' => IstTestSubtest::STATUS_ANSWERING,
            'started_at' => $this->start,
            'answering_started_at' => $this->start,
            'answering_ends_at' => $this->start->addSeconds(240),
        ]);

        return $runtime;
    }

    private function meRuntime(): IstTestSubtest
    {
        $runtime = new IstTestSubtest;
        $runtime->setRawAttributes([
            'status' => IstTestSubtest::STATUS_MEMORIZING,
            'started_at' => $this->start,
            'memorization_started_at' => $this->start,
            'memorization_ends_at' => $this->start->addSeconds(120),
            'answering_started_at' => $this->start->addSeconds(120),
            'answering_ends_at' => $this->start->addSeconds(360),
        ]);

        return $runtime;
    }
}
