<?php

namespace App\Services\Ist\Import\Final;

use App\Exceptions\Ist\UnsafeIstRehearsalPreviewException;
use Illuminate\Support\Facades\DB;

final class IstRehearsalPreviewGuard
{
    public function inspect(int $recordVersion, ?string $explicitDatabase = null): array
    {
        $environment = app()->environment();
        $connection = (string) config('database.default');
        $configuredDatabase = (string) config("database.connections.{$connection}.database");
        $enabled = (bool) config('ist.rehearsal_preview_enabled', false);
        $configuredVersion = (int) config('ist.rehearsal_preview_record_version', 0);
        $allowlist = $this->normalizeAllowlist(
            config('ist.rehearsal_preview_database_allowlist', []),
        );

        $this->assertConfigurationSafe(
            environment: $environment,
            connection: $connection,
            configuredDatabase: $configuredDatabase,
            allowlist: $allowlist,
            enabled: $enabled,
            configuredVersion: $configuredVersion,
            requestedVersion: $recordVersion,
            explicitDatabase: $explicitDatabase,
            versionMin: (int) config('ist.final_version_min', 100000000),
            versionMax: (int) config('ist.final_version_max', 899999999),
        );

        $activeDatabase = DB::connection($connection)
            ->selectOne('select database() as active_database')
            ->active_database ?? null;

        $this->assertActiveDatabaseSafe(
            configuredDatabase: $configuredDatabase,
            activeDatabase: $activeDatabase,
            allowlist: $allowlist,
            explicitDatabase: $explicitDatabase,
        );

        return [
            'environment' => $environment,
            'connection' => $connection,
            'host' => (string) config("database.connections.{$connection}.host"),
            'configured_database' => $configuredDatabase,
            'active_database' => (string) $activeDatabase,
            'record_version' => $recordVersion,
        ];
    }

    public function assertConfigurationSafe(
        string $environment,
        string $connection,
        string $configuredDatabase,
        array $allowlist,
        bool $enabled,
        int $configuredVersion,
        int $requestedVersion,
        ?string $explicitDatabase,
        int $versionMin = 100000000,
        int $versionMax = 899999999,
    ): void {
        if (! $enabled) {
            $this->fail('feature tidak aktif');
        }

        if ($environment !== 'local') {
            $this->fail('APP_ENV harus local');
        }

        if ($connection !== 'mysql') {
            $this->fail('connection harus mysql');
        }

        if ($configuredDatabase === ''
            || in_array($configuredDatabase, ['tes_iq', 'tes_iq_testing'], true)) {
            $this->fail('database utama/testing tidak pernah boleh menjadi target preview');
        }

        if ($allowlist === [] || ! in_array($configuredDatabase, $allowlist, true)) {
            $this->fail('configured database tidak ada dalam allowlist preview');
        }

        if ($explicitDatabase !== null && $explicitDatabase !== $configuredDatabase) {
            $this->fail('database eksplisit tidak sama dengan konfigurasi');
        }

        if ($requestedVersion < $versionMin || $requestedVersion > $versionMax) {
            $this->fail('record version di luar rentang final');
        }

        if ($configuredVersion !== $requestedVersion) {
            $this->fail('record version tidak sama dengan preview version terkonfigurasi');
        }
    }

    public function assertActiveDatabaseSafe(
        string $configuredDatabase,
        mixed $activeDatabase,
        array $allowlist,
        ?string $explicitDatabase,
    ): void {
        if (! is_string($activeDatabase) || $activeDatabase === '') {
            $this->fail('active database kosong');
        }

        if (in_array($activeDatabase, ['tes_iq', 'tes_iq_testing'], true)) {
            $this->fail('active database utama/testing ditolak');
        }

        if ($activeDatabase !== $configuredDatabase) {
            $this->fail('configured dan active database berbeda');
        }

        if (! in_array($activeDatabase, $allowlist, true)) {
            $this->fail('active database tidak ada dalam allowlist preview');
        }

        if ($explicitDatabase !== null && $explicitDatabase !== $activeDatabase) {
            $this->fail('database eksplisit tidak sama dengan database aktif');
        }
    }

    private function normalizeAllowlist(mixed $allowlist): array
    {
        if (! is_array($allowlist)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $allowlist,
        ))));
    }

    private function fail(string $reason): never
    {
        throw UnsafeIstRehearsalPreviewException::because($reason);
    }
}
