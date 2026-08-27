<?php

namespace Tests\Feature\Admin;

use App\Models\IstNormSubtest;
use App\Models\IstNormTotal;
use App\Models\Ist\IstTest;
use App\Models\Ist\IstTestSubtest;
use App\Models\User;
use App\Services\Ist\IstTestLifecycleService;
use Carbon\CarbonImmutable;
use Tests\Feature\Ist\Http\IstHttpTestCase;

class IstResultControllerTest extends IstHttpTestCase
{
    private IstTestLifecycleService $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lifecycle = app(IstTestLifecycleService::class);
    }

    public function test_guest_cannot_access_ist_results_admin(): void
    {
        $this->get(route('admin.ist-results.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_list_and_filter_by_status(): void
    {
        $user = User::factory()->create();
        $completed = $this->completedTest('Selesai');
        $this->seedNorms($completed->age);

        $draft = $this->lifecycle->create([
            'participant_name' => 'Belum Selesai',
            'age' => 30,
            'gender' => 'L',
        ])->test;

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.ist-results.index', ['status' => 'completed']));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/IstResults/Index')
            ->assertJsonPath('props.tests.total', 1)
            ->assertJsonPath('props.tests.data.0.participant_name', 'Selesai');

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.ist-results.index', ['status' => 'draft']));

        $response->assertJsonPath('props.tests.data.0.participant_name', 'Belum Selesai');
    }

    public function test_admin_can_view_completed_result(): void
    {
        $user = User::factory()->create();
        $test = $this->completedTest('Peserta Lengkap');
        $this->seedNorms($test->age);

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.ist-results.show', $test->public_id));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/IstResults/Show')
            ->assertJsonPath('props.result.iqScore', 97)
            ->assertJsonPath('props.result.dominanceProfile', 'M-Dominant (Spatial High)')
            ->assertJsonCount(9, 'props.result.subtests');
    }

    public function test_incomplete_test_shows_unavailable_reason_instead_of_error(): void
    {
        $user = User::factory()->create();
        $draft = $this->lifecycle->create([
            'participant_name' => 'Belum Jadi',
            'age' => 25,
            'gender' => 'P',
        ])->test;

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.ist-results.show', $draft->public_id));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/IstResults/Show')
            ->assertJsonPath('props.result', null);

        $this->assertNotNull(
            json_decode($response->getContent(), true)['props']['unavailableReason'] ?? null,
        );
    }

    private function seedNorms(int $age): void
    {
        $codes = ['SE', 'WA', 'AN', 'GE', 'RA', 'ZR', 'FA', 'WU', 'ME'];

        foreach ($codes as $sequence => $code) {
            $rawScore = $sequence + 1;

            IstNormSubtest::create([
                'subtest' => $code,
                'raw_score' => $rawScore,
                'standard_score' => 90 + $rawScore,
                'min_age' => $age - 5,
                'max_age' => $age + 5,
            ]);
        }

        IstNormTotal::create([
            'total_sw' => 95,
            'iq_score' => 97,
            'iq_category' => 'Rata-rata',
            'min_age' => $age - 5,
            'max_age' => $age + 5,
        ]);
    }

    private function completedTest(string $participant): IstTest
    {
        $startedAt = CarbonImmutable::parse('2026-08-05 09:00:00', 'UTC');
        $finishedAt = $startedAt->addSeconds(2701);

        $creation = $this->lifecycle->create([
            'participant_name' => $participant,
            'age' => 32,
            'gender' => 'P',
        ]);
        $creation->takeRawAccessToken();
        $test = $creation->test;
        $test->update([
            'status' => IstTest::STATUS_COMPLETED,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'current_subtest_sequence' => 9,
        ]);

        foreach ($test->subtests()->get() as $runtime) {
            $runtime->update([
                'status' => $runtime->sequence % 2 === 0
                    ? IstTestSubtest::STATUS_TIMED_OUT
                    : IstTestSubtest::STATUS_COMPLETED,
                'locked_at' => $finishedAt,
                'finalized_reason' => $runtime->sequence % 2 === 0
                    ? IstTestSubtest::FINALIZED_TIMEOUT
                    : IstTestSubtest::FINALIZED_SUBMITTED,
                'awarded_score' => $runtime->sequence,
                'max_score' => 10,
                'correct_count' => $runtime->sequence,
                'partial_count' => 0,
                'wrong_count' => 0,
                'blank_count' => max(0, 10 - $runtime->sequence),
                'percentage' => $runtime->sequence * 10,
            ]);
        }

        return $test->fresh();
    }
}
