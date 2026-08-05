<?php

namespace Tests\Unit\Ist\Import;

use App\Exceptions\Ist\UnsafeIstDevelopmentDatabaseException;
use App\Services\Ist\Import\IstDevelopmentEnvironmentGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IstDevelopmentEnvironmentGuardTest extends TestCase
{
    #[DataProvider('unsafeConfigurationProvider')]
    public function test_unsafe_configuration_is_rejected(string $environment, string $connection, string $database, ?string $allowlist): void
    {
        $this->expectException(UnsafeIstDevelopmentDatabaseException::class);
        (new IstDevelopmentEnvironmentGuard)->assertConfigurationSafe($environment, $connection, $database, $allowlist);
    }

    public static function unsafeConfigurationProvider(): array
    {
        return [
            ['production', 'mysql', 'safe_db', null],
            ['staging', 'mysql', 'safe_db', null],
            ['testing', 'sqlite', 'tes_iq_testing', null],
            ['testing', 'mysql', 'tes_iq', null],
            ['testing', 'mysql', 'other_testing', null],
            ['local', 'mysql', 'local_dev', null],
            ['local', 'mysql', 'local_dev', 'different_db'],
            ['local', 'mysql', 'tes_iq', 'tes_iq'],
        ];
    }

    public function test_safe_testing_and_local_configuration_are_accepted(): void
    {
        $guard = new IstDevelopmentEnvironmentGuard;
        $guard->assertConfigurationSafe('testing', 'mysql', 'tes_iq_testing', null);
        $guard->assertConfigurationSafe('local', 'mysql', 'ist_local_dev', 'ist_local_dev');

        $this->addToAssertionCount(2);
    }

    public function test_configured_and_active_database_must_match(): void
    {
        $this->expectException(UnsafeIstDevelopmentDatabaseException::class);
        (new IstDevelopmentEnvironmentGuard)->assertDatabaseNamesSafe(
            'testing',
            'tes_iq_testing',
            'tes_iq',
            null,
        );
    }
}
