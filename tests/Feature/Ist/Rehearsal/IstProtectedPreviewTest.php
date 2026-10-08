<?php

namespace Tests\Feature\Ist\Rehearsal;

use App\Models\User;
use App\Services\Ist\IstTestLifecycleService;
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

    /**
     * `ist.index` was removed with the merged no-login assessment flow;
     * `ist.resume` sits behind the same `EnsureIstRehearsalPreviewAccess`
     * route-group middleware, so it is an equivalent probe as long as the
     * `{test:public_id}` segment binds to a real, owned row (it also sits
     * behind `EnsureIstTestOwnership`).
     *
     * @return array{0: string, 1: string} [public_id, raw access token]
     */
    private function ownedTestFor(User $owner): array
    {
        $result = app(IstTestLifecycleService::class)->create([
            'participant_name' => 'Rehearsal Probe',
            'age' => 30,
            'gender' => 'L',
        ], null, $owner->id);

        return [$result->test->public_id, $result->takeRawAccessToken()];
    }

    public function test_preview_is_hidden_from_unauthenticated_requests_and_available_to_authenticated_reviewer(): void
    {
        $reviewer = User::query()->firstOrFail();
        [$publicId, $token] = $this->ownedTestFor($reviewer);

        $this->get(route('ist.resume', ['test' => $publicId]))->assertNotFound();

        $this->actingAs($reviewer)
            ->withSession(["ist.access_tokens.{$publicId}" => $token])
            ->get(route('ist.resume', ['test' => $publicId]))
            ->assertStatus(303);
    }

    public function test_wrong_rehearsal_version_is_fail_closed(): void
    {
        $reviewer = User::query()->firstOrFail();
        [$publicId, $token] = $this->ownedTestFor($reviewer);

        config()->set(
            'ist.rehearsal_preview_record_version',
            (int) config('ist.rehearsal_preview_record_version') + 1,
        );

        $this->actingAs($reviewer)
            ->withSession(["ist.access_tokens.{$publicId}" => $token])
            ->get(route('ist.resume', ['test' => $publicId]))
            ->assertNotFound();
    }
}
