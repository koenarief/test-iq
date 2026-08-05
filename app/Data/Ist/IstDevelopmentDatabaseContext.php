<?php

namespace App\Data\Ist;

final readonly class IstDevelopmentDatabaseContext
{
    public function __construct(
        public string $environment,
        public string $connection,
        public string $configuredDatabase,
        public string $activeDatabase,
        public ?string $allowlistedDatabase,
    ) {}

    public function toArray(): array
    {
        return [
            'environment' => $this->environment,
            'connection' => $this->connection,
            'configured_database' => $this->configuredDatabase,
            'active_database' => $this->activeDatabase,
            'allowlisted_database' => $this->allowlistedDatabase,
        ];
    }
}
