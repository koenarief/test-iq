<?php

namespace App\Data\Ist;

use Carbon\CarbonImmutable;

final readonly class IstResultData
{
    public function __construct(
        public string $participantName,
        public int $age,
        public string $gender,
        public string $ageGroup,
        public CarbonImmutable $startedAt,
        public CarbonImmutable $finishedAt,
        public int $durationSeconds,

        public array $subtests,
        public array $graphPoints,

        public array $areaScores,
        public array $areaGraphPoints,

        public float $totalInternalScore,
        public string $performanceCategory,
        public string $performanceBenchmark,

        public array $strongestAreas,
        public array $developmentAreas,

        public float $profileSpread,
        public string $profileBalanceLabel,
    ) {}
}