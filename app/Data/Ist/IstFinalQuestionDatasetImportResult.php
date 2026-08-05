<?php

namespace App\Data\Ist;

final readonly class IstFinalQuestionDatasetImportResult
{
    public function __construct(
        public string $instrumentIdentifier,
        public string $questionBankVersion,
        public int $recordVersion,
        public int $importedQuestions,
        public int $importedExamples,
        public int $importedOptions,
        public int $importedMedia,
        public bool $active,
        public bool $dryRun,
        public array $warnings,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
