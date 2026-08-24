<?php

namespace App\Data\Ist;

use App\Enums\Ist\IstFinalizationReason;
use Carbon\CarbonImmutable;

final readonly class IstSubtestFinalizationResult
{
    public function __construct(
        public int $testSubtestId,
        public string $subtestCode,
        public int $sequence,
        public string $status,
        public IstFinalizationReason $persistedReason,
        public bool $alreadyFinalized,
        public float $awardedScore,
        public float $maxScore,
        public int $correctCount,
        public int $partialCount,
        public int $wrongCount,
        public int $blankCount,
        public float $percentage,
        public CarbonImmutable $lockedAt,
        public ?int $nextTestSubtestId,
        public bool $overallCompleted,
    ) {}
}
