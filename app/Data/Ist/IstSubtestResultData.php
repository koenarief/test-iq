<?php

namespace App\Data\Ist;

final readonly class IstSubtestResultData
{
    public function __construct(
        public string $code,
        public string $name,
        public int $sequence,
        public int $rawScore,
        public ?int $standardScore,
        public int $correctCount,
        public int $partialCount,
        public int $wrongCount,
        public int $blankCount,
    ) {}
}
