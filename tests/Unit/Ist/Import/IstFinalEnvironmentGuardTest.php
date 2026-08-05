<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\UnsafeIstFinalDatabaseException;
use App\Services\Ist\Import\Final\IstFinalEnvironmentGuard;
use PHPUnit\Framework\TestCase;

final class IstFinalEnvironmentGuardTest extends TestCase
{
    private IstFinalEnvironmentGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new IstFinalEnvironmentGuard;
    }

    public function test_main_database_is_rejected(): void
    {
        $this->expectException(UnsafeIstFinalDatabaseException::class);

        $this->guard->assertConfigurationSafe(
            'local',
            'mysql',
            'tes_iq',
            ['tes_iq'],
            'tes_iq',
            true,
        );
    }

    public function test_configured_and_active_database_mismatch_is_rejected(): void
    {
        $this->expectException(UnsafeIstFinalDatabaseException::class);

        $this->guard->assertDatabaseNamesSafe(
            'tes_iq_ist_dev',
            'tes_iq_ist_test',
            ['tes_iq_ist_dev', 'tes_iq_ist_test'],
            'tes_iq_ist_dev',
            true,
        );
    }

    public function test_write_requires_explicit_matching_database(): void
    {
        $this->expectException(UnsafeIstFinalDatabaseException::class);

        $this->guard->assertConfigurationSafe(
            'testing',
            'mysql',
            'tes_iq_testing',
            ['tes_iq_testing'],
            null,
            true,
        );
    }

    public function test_allowlisted_local_database_passes_pure_configuration_checks(): void
    {
        $this->guard->assertConfigurationSafe(
            'local',
            'mysql',
            'tes_iq_ist_dev',
            ['tes_iq_ist_dev'],
            'tes_iq_ist_dev',
            true,
        );
        $this->guard->assertDatabaseNamesSafe(
            'tes_iq_ist_dev',
            'tes_iq_ist_dev',
            ['tes_iq_ist_dev'],
            'tes_iq_ist_dev',
            true,
        );

        $this->addToAssertionCount(1);
    }
}
