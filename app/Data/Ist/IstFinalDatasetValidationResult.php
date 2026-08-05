<?php

namespace App\Data\Ist;

final readonly class IstFinalDatasetValidationResult
{
    public function __construct(
        public string $directory,
        public array $manifest,
        public array $approvals,
        public array $checksums,
        public array $subtests,
        public array $media,
        public int $scoredQuestionCount,
        public int $exampleQuestionCount,
        public int $optionCount,
    ) {}
}
