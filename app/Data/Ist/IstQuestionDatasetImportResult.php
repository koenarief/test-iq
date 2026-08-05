<?php

namespace App\Data\Ist;

final readonly class IstQuestionDatasetImportResult
{
    public function __construct(
        public string $dataset,
        public int $recordVersion,
        public int $subtestCount,
        public int $scoredQuestionCount,
        public int $exampleQuestionCount,
        public int $optionCount,
        public int $mediaCount,
        public int $createdQuestionCount,
        public int $updatedQuestionCount,
        public int $restoredQuestionCount,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
