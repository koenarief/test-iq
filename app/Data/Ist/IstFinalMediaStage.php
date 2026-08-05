<?php

namespace App\Data\Ist;

final readonly class IstFinalMediaStage
{
    public function __construct(
        public string $disk,
        public string $stagingDirectory,
        public string $finalDirectory,
        public array $files,
    ) {}
}
