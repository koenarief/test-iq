<?php

namespace App\Data\Ist;

use Carbon\CarbonImmutable;

final readonly class IstAutosaveResult
{
    public function __construct(
        public array $savedQuestionIds,
        public array $ignoredStaleQuestionIds,
        public array $idempotentQuestionIds,
        public CarbonImmutable $savedAt,
        public int $remainingSeconds,
    ) {}
}
