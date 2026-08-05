<?php

namespace App\Services\Ist\Import\Final;

use App\Data\Ist\IstFinalDatabaseContext;
use App\Exceptions\Ist\UnsafeIstFinalDatabaseException;
use Illuminate\Support\Facades\DB;

final class IstFinalEnvironmentGuard
{
    public function inspect(?string $explicitDatabase = null, bool $write = false): IstFinalDatabaseContext
    {
        $environment = app()->environment();
        $connection = (string) config('database.default');
        $configured = (string) config("database.connections.{$connection}.database");
        $allowlist = config('ist.final_database_allowlist', []);

        if (! is_array($allowlist)) {
            $allowlist = [];
        }

        $allowlist = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $allowlist,
        ))));

        $this->assertConfigurationSafe(
            $environment,
            $connection,
            $configured,
            $allowlist,
            $explicitDatabase,
            $write,
        );

        $active = DB::connection($connection)
            ->selectOne('select database() as active_database')
            ->active_database ?? null;

        $this->assertDatabaseNamesSafe(
            $configured,
            $active,
            $allowlist,
            $explicitDatabase,
            $write,
        );

        return new IstFinalDatabaseContext(
            environment: $environment,
            connection: $connection,
            configuredDatabase: $configured,
            activeDatabase: (string) $active,
            allowlistedDatabases: $allowlist,
            writeAuthorized: $write,
        );
    }

    public function assertConfigurationSafe(
        string $environment,
        string $connection,
        string $configuredDatabase,
        array $allowlist,
        ?string $explicitDatabase,
        bool $write,
    ): void {
        if (! in_array($environment, ['local', 'testing'], true)) {
            throw UnsafeIstFinalDatabaseException::because('APP_ENV harus local atau testing');
        }

        if ($connection !== 'mysql') {
            throw UnsafeIstFinalDatabaseException::because('connection harus mysql');
        }

        if ($configuredDatabase === '' || $configuredDatabase === 'tes_iq') {
            throw UnsafeIstFinalDatabaseException::because('configured database kosong atau merupakan database utama');
        }

        if ($allowlist === [] || ! in_array($configuredDatabase, $allowlist, true)) {
            throw UnsafeIstFinalDatabaseException::because('configured database tidak ada dalam allowlist eksplisit');
        }

        if ($write && ($explicitDatabase === null || trim($explicitDatabase) === '')) {
            throw UnsafeIstFinalDatabaseException::because('operasi tulis memerlukan --allow-database');
        }

        if ($write && $explicitDatabase !== $configuredDatabase) {
            throw UnsafeIstFinalDatabaseException::because('database yang diizinkan tidak sama dengan konfigurasi');
        }
    }

    public function assertDatabaseNamesSafe(
        string $configuredDatabase,
        mixed $activeDatabase,
        array $allowlist,
        ?string $explicitDatabase,
        bool $write,
    ): void {
        if (! is_string($activeDatabase) || $activeDatabase === '' || $activeDatabase === 'tes_iq') {
            throw UnsafeIstFinalDatabaseException::because('active database kosong atau merupakan database utama');
        }

        if ($activeDatabase !== $configuredDatabase) {
            throw UnsafeIstFinalDatabaseException::because('configured dan active database berbeda');
        }

        if (! in_array($activeDatabase, $allowlist, true)) {
            throw UnsafeIstFinalDatabaseException::because('active database tidak ada dalam allowlist eksplisit');
        }

        if ($write && $explicitDatabase !== $activeDatabase) {
            throw UnsafeIstFinalDatabaseException::because('database yang diizinkan tidak sama dengan database aktif');
        }
    }
}
