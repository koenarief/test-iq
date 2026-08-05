<?php

namespace App\Services\Ist\Import;

use App\Data\Ist\IstDevelopmentDatabaseContext;
use App\Exceptions\Ist\UnsafeIstDevelopmentDatabaseException;
use Illuminate\Support\Facades\DB;

final class IstDevelopmentEnvironmentGuard
{
    public function assertSafe(): IstDevelopmentDatabaseContext
    {
        $environment = app()->environment();
        $connection = (string) config('database.default');
        $configured = (string) config("database.connections.{$connection}.database");
        $allowlisted = config('ist.development_database');

        $this->assertConfigurationSafe($environment, $connection, $configured, $allowlisted);

        $active = DB::connection($connection)->selectOne('select database() as active_database')->active_database ?? null;

        $this->assertDatabaseNamesSafe($environment, $configured, $active, $allowlisted);

        return new IstDevelopmentDatabaseContext(
            $environment,
            $connection,
            $configured,
            (string) $active,
            is_string($allowlisted) && $allowlisted !== '' ? $allowlisted : null,
        );
    }

    public function assertConfigurationSafe(
        string $environment,
        string $connection,
        string $configuredDatabase,
        mixed $allowlistedDatabase,
    ): void {
        if (! in_array($environment, ['local', 'testing'], true)) {
            throw UnsafeIstDevelopmentDatabaseException::because('environment harus local atau testing');
        }

        if ($connection !== 'mysql') {
            throw UnsafeIstDevelopmentDatabaseException::because('connection harus mysql');
        }

        if ($configuredDatabase === '' || $configuredDatabase === 'tes_iq') {
            throw UnsafeIstDevelopmentDatabaseException::because('configured database tidak aman');
        }

        if ($environment === 'testing' && $configuredDatabase !== 'tes_iq_testing') {
            throw UnsafeIstDevelopmentDatabaseException::because('database testing harus tes_iq_testing');
        }

        if ($environment === 'local'
            && (! is_string($allowlistedDatabase)
                || $allowlistedDatabase === ''
                || $allowlistedDatabase === 'tes_iq'
                || $configuredDatabase !== $allowlistedDatabase)) {
            throw UnsafeIstDevelopmentDatabaseException::because('database local tidak cocok dengan allowlist');
        }
    }

    public function assertDatabaseNamesSafe(
        string $environment,
        string $configuredDatabase,
        mixed $activeDatabase,
        mixed $allowlistedDatabase,
    ): void {
        if (! is_string($activeDatabase) || $activeDatabase === '' || $activeDatabase === 'tes_iq') {
            throw UnsafeIstDevelopmentDatabaseException::because('active database tidak aman');
        }

        if ($activeDatabase !== $configuredDatabase) {
            throw UnsafeIstDevelopmentDatabaseException::because('active database berbeda dari konfigurasi');
        }

        if ($environment === 'testing' && $activeDatabase !== 'tes_iq_testing') {
            throw UnsafeIstDevelopmentDatabaseException::because('active testing database harus tes_iq_testing');
        }

        if ($environment === 'local' && $activeDatabase !== $allowlistedDatabase) {
            throw UnsafeIstDevelopmentDatabaseException::because('active local database berbeda dari allowlist');
        }
    }
}
