<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\UnsafeIstRehearsalPreviewException;
use App\Services\Ist\Import\Final\IstRehearsalPreviewGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IstRehearsalPreviewGuardTest extends TestCase
{
    private IstRehearsalPreviewGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guard = new IstRehearsalPreviewGuard;
    }

    public function test_exact_allowlisted_local_clone_and_version_are_accepted(): void
    {
        $this->guard->assertConfigurationSafe(
            environment: 'local',
            connection: 'mysql',
            configuredDatabase: 'tes_iq_migration_test',
            allowlist: ['tes_iq_migration_test'],
            enabled: true,
            configuredVersion: 100000017,
            requestedVersion: 100000017,
            explicitDatabase: 'tes_iq_migration_test',
        );
        $this->guard->assertActiveDatabaseSafe(
            configuredDatabase: 'tes_iq_migration_test',
            activeDatabase: 'tes_iq_migration_test',
            allowlist: ['tes_iq_migration_test'],
            explicitDatabase: 'tes_iq_migration_test',
        );

        $this->addToAssertionCount(1);
    }

    #[DataProvider('unsafeConfigurationProvider')]
    public function test_unsafe_preview_configuration_is_rejected(array $arguments): void
    {
        $this->expectException(UnsafeIstRehearsalPreviewException::class);

        $this->guard->assertConfigurationSafe(...$arguments);
    }

    public static function unsafeConfigurationProvider(): array
    {
        $safe = [
            'environment' => 'local',
            'connection' => 'mysql',
            'configuredDatabase' => 'tes_iq_migration_test',
            'allowlist' => ['tes_iq_migration_test'],
            'enabled' => true,
            'configuredVersion' => 100000017,
            'requestedVersion' => 100000017,
            'explicitDatabase' => 'tes_iq_migration_test',
        ];

        return [
            'disabled' => [[...$safe, 'enabled' => false]],
            'production environment' => [[...$safe, 'environment' => 'production']],
            'primary database' => [[
                ...$safe,
                'configuredDatabase' => 'tes_iq',
                'allowlist' => ['tes_iq'],
                'explicitDatabase' => 'tes_iq',
            ]],
            'testing database' => [[
                ...$safe,
                'configuredDatabase' => 'tes_iq_testing',
                'allowlist' => ['tes_iq_testing'],
                'explicitDatabase' => 'tes_iq_testing',
            ]],
            'version mismatch' => [[...$safe, 'requestedVersion' => 100000018]],
            'database mismatch' => [[...$safe, 'explicitDatabase' => 'other_clone']],
        ];
    }

    public function test_active_database_mismatch_is_rejected(): void
    {
        $this->expectException(UnsafeIstRehearsalPreviewException::class);

        $this->guard->assertActiveDatabaseSafe(
            configuredDatabase: 'tes_iq_migration_test',
            activeDatabase: 'tes_iq',
            allowlist: ['tes_iq_migration_test'],
            explicitDatabase: 'tes_iq_migration_test',
        );
    }
}
