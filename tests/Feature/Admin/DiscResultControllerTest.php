<?php

namespace Tests\Feature\Admin;

use App\Models\DiscProfile;
use App\Models\DiscTest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DiscResultControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestingDatabase();

        $this->withoutVite();
        $manifest = public_path('build/manifest.json');

        if (is_file($manifest)) {
            $this->withHeader('X-Inertia-Version', hash_file('xxh128', $manifest));
        }
    }

    public function test_guest_cannot_access_disc_results_admin(): void
    {
        $this->get(route('admin.disc-results.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_list_and_filter_by_status(): void
    {
        $user = User::factory()->create();
        DiscTest::create([
            'participant_name' => 'Selesai',
            'age' => 28,
            'gender' => 'L',
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'disc_type' => 'D',
            'primary_type' => 'D',
            'secondary_type' => 'I',
        ]);
        DiscTest::create([
            'participant_name' => 'Draft',
            'age' => 22,
            'gender' => 'P',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.disc-results.index', ['status' => 'completed']));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/DiscResults/Index')
            ->assertJsonPath('props.tests.total', 1)
            ->assertJsonPath('props.tests.data.0.participant_name', 'Selesai');
    }

    public function test_admin_can_view_completed_disc_result_with_profile(): void
    {
        $user = User::factory()->create();
        $profile = DiscProfile::create([
            'code' => 'D',
            'name' => 'Sang Penggerak',
            'title' => 'The Driver',
            'summary' => 'Tegas dan berorientasi hasil.',
            'strength' => ['tegas', 'cepat mengambil keputusan'],
            'weakness' => ['kurang sabar'],
            'communication' => ['langsung'],
            'leadership' => ['memimpin dari depan'],
            'motivation' => ['tantangan'],
            'stress' => ['frustrasi bila lambat'],
            'development' => ['belajar sabar'],
            'job_match' => ['Manajer Proyek', 'Wirausaha'],
        ]);

        $discTest = DiscTest::create([
            'participant_name' => 'Peserta DISC',
            'age' => 30,
            'gender' => 'L',
            'status' => 'completed',
            'started_at' => now()->subMinutes(30),
            'finished_at' => now(),
            'most_d' => 8, 'most_i' => 2, 'most_s' => 1, 'most_c' => 1,
            'least_d' => 1, 'least_i' => 2, 'least_s' => 5, 'least_c' => 4,
            'change_d' => 7, 'change_i' => 0, 'change_s' => -4, 'change_c' => -3,
            'graph_d' => 90, 'graph_i' => 60, 'graph_s' => 40, 'graph_c' => 45,
            'primary_type' => 'D',
            'secondary_type' => 'I',
            'disc_type' => 'D',
            'disc_profile_id' => $profile->id,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.disc-results.show', $discTest->id));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/DiscResults/Show')
            ->assertJsonPath('props.test.disc_type', 'D')
            ->assertJsonPath('props.test.graph_d', 90)
            ->assertJsonPath('props.profile.name', 'Sang Penggerak');
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
            throw new RuntimeException('DISC admin test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('DISC admin test safety guard rejected the active database.');
        }
    }
}
