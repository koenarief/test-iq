<?php

namespace App\Data\Ist;

use App\Enums\Ist\IstAccessDestination;
use App\Enums\Ist\IstSubtestPhase;
use Carbon\CarbonImmutable;

final readonly class IstSubtestAccessDecision
{
    public function __construct(
        public IstAccessDestination $destination,
        public ?int $canonicalTestSubtestId,
        public ?IstSubtestPhase $phase,
        public bool $requestedAccessAllowed,
        public bool $answeringAllowed,
        public bool $snapshotComplete,
        public ?CarbonImmutable $phaseEndsAt,
        public int $remainingSeconds,
    ) {}
}
