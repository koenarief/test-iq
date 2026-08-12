<?php

namespace App\Services\Ist\Import\Final;

use App\Data\Ist\IstFinalDatabaseContext;
use App\Exceptions\Ist\UnsafeIstFinalDatabaseException;
use Illuminate\Support\Facades\DB;

final class IstFinalEnvironmentGuard
{
    public function inspect(
        ?string $explicitDatabase = null,
        bool $write = false,
        bool $testFixture = false,
    ): IstFinalDatabaseContext {
        $environment = app()->environment();
        $connection = (string) config('database.default');
        $host = (string) config("database.connections.{$connection}.host");
        $configured = (string) config("database.connections.{$connection}.database");
        $allowlist = config('ist.final_database_allowlist', []);
        $primaryDatabase = (string) config('ist.final_primary_database', 'tes_iq');
        $primaryWriteEnabled = (bool) config('ist.final_primary_write_enabled', false);
        $testingDatabase = (string) config('ist.final_testing_database', 'tes_iq_testing');

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
            $testFixture,
            $primaryDatabase,
            $primaryWriteEnabled,
            $testingDatabase,
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
            $testFixture,
            $primaryDatabase,
            $primaryWriteEnabled,
            $testingDatabase,
        );

        return new IstFinalDatabaseContext(
            environment: $environment,
            connection: $connection,
            host: $host,
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
        bool $testFixture = false,
        string $primaryDatabase = 'tes_iq',
        bool $primaryWriteEnabled = false,
        string $testingDatabase = 'tes_iq_testing',
    ): void {
        $environmentAllowed =
            $environment === 'local'
            || ($environment === 'production'
                && $configuredDatabase === $primaryDatabase
                && $primaryWriteEnabled)
            || ($environment === 'testing' && $testFixture);

        if (! $environmentAllowed) {
            throw UnsafeIstFinalDatabaseException::because(
                'environment tidak diizinkan untuk final dataset'
            );
        }

        if ($connection !== 'mysql') {
            throw UnsafeIstFinalDatabaseException::because('connection harus mysql');
        }

        if ($configuredDatabase === '') {
            throw UnsafeIstFinalDatabaseException::because('configured database kosong');
        }

        if ($configuredDatabase === $testingDatabase && ! $testFixture) {
            throw UnsafeIstFinalDatabaseException::because('database automated test tidak boleh menjadi target final dataset');
        }

        if ($configuredDatabase === $primaryDatabase && ! $primaryWriteEnabled) {
            throw UnsafeIstFinalDatabaseException::because('database utama belum diizinkan oleh production migration gate');
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
        bool $testFixture = false,
        string $primaryDatabase = 'tes_iq',
        bool $primaryWriteEnabled = false,
        string $testingDatabase = 'tes_iq_testing',
    ): void {
        if (! is_string($activeDatabase) || $activeDatabase === '') {
            throw UnsafeIstFinalDatabaseException::because('active database kosong');
        }

        if ($activeDatabase === $testingDatabase && ! $testFixture) {
            throw UnsafeIstFinalDatabaseException::because('database automated test tidak boleh menjadi target final dataset');
        }

        if ($activeDatabase === $primaryDatabase && ! $primaryWriteEnabled) {
            throw UnsafeIstFinalDatabaseException::because('database utama belum diizinkan oleh production migration gate');
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
