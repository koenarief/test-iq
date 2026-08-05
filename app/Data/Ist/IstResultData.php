<?php

namespace App\Data\Ist;

use Carbon\CarbonImmutable;

final readonly class IstResultData
{
    public function __construct(
        public string $participantName,
        public int $age,
        public string $gender,
        public CarbonImmutable $startedAt,
        public CarbonImmutable $finishedAt,
        public int $durationSeconds,
        public array $subtests,
        public array $graphPoints,
        public float $totalInternalScore,
    ) {}
}
