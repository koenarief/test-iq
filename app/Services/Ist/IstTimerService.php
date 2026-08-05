<?php

namespace App\Services\Ist;

use App\Enums\Ist\IstSubtestPhase;
use App\Models\Ist\IstTestSubtest;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class IstTimerService
{
    public function getCurrentPhase(
        IstTestSubtest $testSubtest,
        CarbonInterface $now,
    ): IstSubtestPhase {
        if ($testSubtest->status === IstTestSubtest::STATUS_COMPLETED) {
            return IstSubtestPhase::COMPLETED;
        }

        if ($testSubtest->status === IstTestSubtest::STATUS_TIMED_OUT) {
            return IstSubtestPhase::TIMED_OUT;
        }

        if ($this->deadlineReached($testSubtest->answering_ends_at, $now)) {
            return IstSubtestPhase::TIMED_OUT;
        }

        if ($testSubtest->status === IstTestSubtest::STATUS_INSTRUCTION) {
            return IstSubtestPhase::INSTRUCTION;
        }

        if ($testSubtest->memorization_ends_at !== null
            && $this->isBefore($now, $testSubtest->memorization_ends_at)) {
            return IstSubtestPhase::MEMORIZING;
        }

        return IstSubtestPhase::ANSWERING;
    }

    public function getPhaseEndsAt(
        IstTestSubtest $testSubtest,
        CarbonInterface $now,
    ): ?CarbonImmutable {
        return match ($this->getCurrentPhase($testSubtest, $now)) {
            IstSubtestPhase::MEMORIZING => $this->immutable($testSubtest->memorization_ends_at),
            IstSubtestPhase::ANSWERING => $this->immutable($testSubtest->answering_ends_at),
            default => null,
        };
    }

    public function isExpired(
        IstTestSubtest $testSubtest,
        CarbonInterface $now,
    ): bool {
        if ($testSubtest->status === IstTestSubtest::STATUS_COMPLETED) {
            return false;
        }

        if ($testSubtest->status === IstTestSubtest::STATUS_TIMED_OUT) {
            return true;
        }

        return $this->deadlineReached($testSubtest->answering_ends_at, $now);
    }

    public function getRemainingSeconds(
        IstTestSubtest $testSubtest,
        CarbonInterface $now,
    ): int {
        $endsAt = $this->getPhaseEndsAt($testSubtest, $now);

        if ($endsAt === null) {
            return 0;
        }

        $remaining = (float) $endsAt->format('U.u') - (float) $now->format('U.u');

        return max(0, (int) ceil($remaining));
    }

    private function deadlineReached(mixed $deadline, CarbonInterface $now): bool
    {
        return $deadline !== null && ! $this->isBefore($now, $deadline);
    }

    private function isBefore(CarbonInterface $now, mixed $deadline): bool
    {
        return (float) $now->format('U.u') < (float) $deadline->format('U.u');
    }

    private function immutable(mixed $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::instance($value);
    }
}
