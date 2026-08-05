<?php

namespace App\Data\Ist;

final readonly class IstFinalDatabaseContext
{
    public function __construct(
        public string $environment,
        public string $connection,
        public string $configuredDatabase,
        public string $activeDatabase,
        public array $allowlistedDatabases,
        public bool $writeAuthorized,
    ) {}
}
