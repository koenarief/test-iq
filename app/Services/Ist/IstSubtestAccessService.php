<?php

namespace App\Services\Ist;

use App\Data\Ist\IstSubtestAccessDecision;
use App\Enums\Ist\IstAccessDestination;
use App\Enums\Ist\IstSubtestPhase;
use App\Exceptions\Ist\InvalidIstSubtestAccessStateException;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use Carbon\CarbonInterface;

final class IstSubtestAccessService
{
    public function __construct(
        private readonly IstTimerService $timer,
    ) {}

    public function decide(
        IstTest $test,
        IstAccessDestination $requestedDestination,
        ?IstTestSubtest $requestedSubtest,
        CarbonInterface $now,
    ): IstSubtestAccessDecision {
        $test = $test->fresh();

        if (! $test) {
            throw new InvalidIstSubtestAccessStateException(0, 'test does not exist');
        }

        if ($test->status === IstTest::STATUS_COMPLETED) {
            return new IstSubtestAccessDecision(
                destination: IstAccessDestination::RESULT,
                canonicalTestSubtestId: null,
                phase: null,
                requestedAccessAllowed: $requestedDestination === IstAccessDestination::RESULT,
                answeringAllowed: false,
                snapshotComplete: true,
                phaseEndsAt: null,
                remainingSeconds: 0,
            );
        }

        if ($test->status === IstTest::STATUS_CANCELLED) {
            return new IstSubtestAccessDecision(
                destination: IstAccessDestination::UNAVAILABLE,
                canonicalTestSubtestId: null,
                phase: null,
                requestedAccessAllowed: false,
                answeringAllowed: false,
                snapshotComplete: false,
                phaseEndsAt: null,
                remainingSeconds: 0,
            );
        }

        $this->assertOverallStateIsConsistent($test);

        $current = IstTestSubtest::query()
            ->where('ist_test_id', $test->id)
            ->where('sequence', $test->current_subtest_sequence)
            ->withCount('testQuestions')
            ->first();

        if (! $current) {
            throw new InvalidIstSubtestAccessStateException(
                $test->id,
                'current runtime subtest is missing',
            );
        }

        $snapshotComplete = $current->test_questions_count === $current->question_count;
        $phase = $this->timer->getCurrentPhase($current, $now);

        if (in_array($phase, [
            IstSubtestPhase::MEMORIZING,
            IstSubtestPhase::ANSWERING,
        ], true) && ! $snapshotComplete) {
            throw new InvalidIstSubtestAccessStateException(
                $test->id,
                'active runtime subtest has an incomplete snapshot',
            );
        }

        $destination = match ($phase) {
            IstSubtestPhase::INSTRUCTION => IstAccessDestination::INSTRUCTION,
            IstSubtestPhase::MEMORIZING => IstAccessDestination::MEMORIZATION,
            IstSubtestPhase::ANSWERING => IstAccessDestination::WORK,
            IstSubtestPhase::TIMED_OUT => IstAccessDestination::EXPIRED,
            IstSubtestPhase::COMPLETED => IstAccessDestination::COMPLETED,
        };

        $requestedRuntimeMatches = $requestedSubtest !== null
            && $requestedSubtest->ist_test_id === $test->id
            && $requestedSubtest->id === $current->id;
        $requestedAccessAllowed = $requestedRuntimeMatches
            && $requestedDestination === $destination;
        $answeringAllowed = $destination === IstAccessDestination::WORK
            && $snapshotComplete
            && ! $this->timer->isExpired($current, $now);

        return new IstSubtestAccessDecision(
            destination: $destination,
            canonicalTestSubtestId: $current->id,
            phase: $phase,
            requestedAccessAllowed: $requestedAccessAllowed,
            answeringAllowed: $answeringAllowed,
            snapshotComplete: $snapshotComplete,
            phaseEndsAt: $this->timer->getPhaseEndsAt($current, $now),
            remainingSeconds: $this->timer->getRemainingSeconds($current, $now),
        );
    }

    private function assertOverallStateIsConsistent(IstTest $test): void
    {
        if ($test->status === IstTest::STATUS_IN_PROGRESS && $test->started_at === null) {
            throw new InvalidIstSubtestAccessStateException(
                $test->id,
                'in-progress test has no start time',
            );
        }

        if ($test->status === IstTest::STATUS_DRAFT && $test->started_at !== null) {
            throw new InvalidIstSubtestAccessStateException(
                $test->id,
                'draft test already has a start time',
            );
        }

        if (! in_array($test->status, [
            IstTest::STATUS_DRAFT,
            IstTest::STATUS_IN_PROGRESS,
        ], true)) {
            throw new InvalidIstSubtestAccessStateException(
                $test->id,
                'test status is unsupported',
            );
        }
    }
}
