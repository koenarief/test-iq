<?php

namespace Tests\Feature\Ist\Services;

use App\Models\Ist\IstSubtest;
use App\Support\Ist\IstSubtestCatalog;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

abstract class IstDatabaseTestCase extends TestCase
{
    private bool $istTransactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        DB::beginTransaction();
        $this->istTransactionStarted = true;

        $this->restoreIstCatalogFixture();
    }

    protected function tearDown(): void
    {
        if ($this->istTransactionStarted && DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        $this->istTransactionStarted = false;

        parent::tearDown();
    }

    private function assertSafeTestingDatabase(): void
    {
        $environment = app()->environment();
        $connection = config('database.default');
        $configuredDatabase = config("database.connections.{$connection}.database");

        if ($environment !== 'testing'
            || $connection !== 'mysql'
            || $configuredDatabase !== 'tes_iq_testing'
            || $configuredDatabase === 'tes_iq') {
            throw new RuntimeException('IST database test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('IST database test safety guard rejected the active database.');
        }
    }

    private function restoreIstCatalogFixture(): void
    {
        IstSubtest::query()->update(['is_active' => false]);

        foreach (IstSubtestCatalog::all() as $definition) {
            IstSubtest::updateOrCreate(
                ['code' => $definition['code']],
                [...$definition, 'is_active' => true],
            );
        }
    }
}
