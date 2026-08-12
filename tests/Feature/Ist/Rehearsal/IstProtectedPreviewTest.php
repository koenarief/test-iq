<?php

namespace Tests\Feature\Ist\Rehearsal;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class IstProtectedPreviewTest extends TestCase
{
    private const CLONE_DATABASE = 'tes_iq_migration_test';

    private bool $transactionStarted = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! app()->environment('local')
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== self::CLONE_DATABASE
            || ! config('ist.rehearsal_preview_enabled')) {
            $this->markTestSkipped('Protected preview test hanya berjalan pada clone rehearsal local.');
        }

        $activeDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($activeDatabase !== self::CLONE_DATABASE || $activeDatabase === 'tes_iq') {
            throw new \RuntimeException('Protected preview test menolak database aktif.');
        }

        DB::beginTransaction();
        $this->transactionStarted = true;
        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        if ($this->transactionStarted && DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        parent::tearDown();
    }

    public function test_preview_is_hidden_from_unauthenticated_requests_and_available_to_authenticated_reviewer(): void
    {
        $this->get(route('ist.index'))->assertNotFound();

        $reviewer = User::query()->firstOrFail();
        $this->actingAs($reviewer)
            ->get(route('ist.index'))
            ->assertOk();
    }

    public function test_wrong_rehearsal_version_is_fail_closed(): void
    {
        config()->set(
            'ist.rehearsal_preview_record_version',
            (int) config('ist.rehearsal_preview_record_version') + 1,
        );

        $this->actingAs(User::query()->firstOrFail())
            ->get(route('ist.index'))
            ->assertNotFound();
    }
}
