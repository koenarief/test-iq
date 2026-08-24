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

        public int $totalRawScore,
        public ?int $totalStandardScore,
        public ?int $iqScore,
        public ?string $iqCategory,
        public ?string $dominanceProfile,
    ) {}
}
