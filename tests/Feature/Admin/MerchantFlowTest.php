<?php

namespace Tests\Feature\Admin;

use App\Models\CompetencyTest;
use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\Instansi;
use App\Models\Merchant;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Feature\Ist\Http\IstHttpTestCase;

class MerchantFlowTest extends IstHttpTestCase
{
    private function instansi(): Instansi
    {
        return Instansi::firstOrCreate(['nama' => 'Instansi Uji']);
    }

    private function position(): Position
    {
        return Position::create([
            'name' => 'Staff Uji',
            'department' => 'sales',
            'is_active' => true,
        ]);
    }

    private function beginAssessment(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeader('X-Inertia', 'true')->post(route('assessment.begin'), array_merge([
            'participant_name' => 'Peserta Uji',
            'age' => 25,
            'gender' => 'L',
            'instansi_id' => $this->instansi()->id,
            'position_id' => $this->position()->id,
        ], $overrides));
    }

    public function test_guest_cannot_access_merchant_admin(): void
    {
        $this->get(route('admin.merchants.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_create_and_list_merchants(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.merchants.store'), [
            'name' => 'Merchant Satu',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('admin.merchants.index'));
        $this->assertDatabaseHas('merchants', ['name' => 'Merchant Satu', 'is_active' => 1]);

        $merchant = Merchant::where('name', 'Merchant Satu')->firstOrFail();
        $this->assertNotEmpty($merchant->public_id);

        $indexResponse = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.merchants.index'));

        $indexResponse->assertOk()
            ->assertJsonPath('component', 'Admin/Merchants/Index')
            ->assertJsonPath('props.merchants.data.0.id', $merchant->id)
            ->assertJsonPath('props.merchants.data.0.public_id', $merchant->public_id);
    }

    public function test_admin_can_update_and_deactivate_merchant(): void
    {
        $admin = User::factory()->create();
        $merchant = Merchant::create(['name' => 'Lama', 'is_active' => true]);

        $response = $this->actingAs($admin)->put(route('admin.merchants.update', $merchant), [
            'name' => 'Baru',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('admin.merchants.index'));
        $merchant->refresh();
        $this->assertSame('Baru', $merchant->name);
        $this->assertFalse($merchant->is_active);
    }

    public function test_edit_page_exposes_merchant_start_url(): void
    {
        $admin = User::factory()->create();
        $merchant = Merchant::create(['name' => 'Punya URL', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.merchants.edit', $merchant));

        $response->assertOk()
            ->assertJsonPath('props.startUrl', route('assessment.start.merchant', $merchant));

        $this->assertStringContainsString($merchant->slug, $response->json('props.startUrl'));
        $this->assertStringNotContainsString('/m/'.$merchant->id, $response->json('props.startUrl'));
        $this->assertStringNotContainsString($merchant->public_id, $response->json('props.startUrl'));
    }

    public function test_deleting_merchant_keeps_existing_tests_but_unlinks_them(): void
    {
        $admin = User::factory()->create();
        $merchant = Merchant::create(['name' => 'Akan Dihapus', 'is_active' => true]);
        $discTest = DiscTest::create([
            'participant_name' => 'Peserta',
            'age' => 25,
            'gender' => 'L',
            'status' => 'draft',
            'merchant_id' => $merchant->id,
        ]);

        $this->actingAs($admin)->delete(route('admin.merchants.destroy', $merchant));

        $this->assertDatabaseMissing('merchants', ['id' => $merchant->id]);
        $this->assertDatabaseHas('disc_tests', ['id' => $discTest->id, 'merchant_id' => null]);
    }

    public function test_assessment_begin_via_merchant_url_tags_the_created_disc_test(): void
    {
        $merchant = Merchant::create(['name' => 'Merchant DISC', 'is_active' => true]);

        $this->withHeader('X-Inertia', 'true')
            ->get(route('assessment.start.merchant', $merchant))
            ->assertOk();

        $response = $this->beginAssessment(['participant_name' => 'Peserta DISC Merchant']);

        $test = DiscTest::where('participant_name', 'Peserta DISC Merchant')->firstOrFail();
        $this->assertSame($merchant->id, $test->merchant_id);
        $response->assertRedirect(route('disc.instruction', $test->id));
    }

    public function test_assessment_begin_without_merchant_url_leaves_merchant_null(): void
    {
        $this->withHeader('X-Inertia', 'true')
            ->get(route('assessment.start'))
            ->assertOk();

        $this->beginAssessment(['participant_name' => 'Peserta DISC Umum']);

        $test = DiscTest::where('participant_name', 'Peserta DISC Umum')->firstOrFail();
        $this->assertNull($test->merchant_id);
    }

    public function test_assessment_merchant_context_does_not_leak_into_a_later_general_begin(): void
    {
        $merchant = Merchant::create(['name' => 'Merchant Bocor', 'is_active' => true]);

        $this->withHeader('X-Inertia', 'true')
            ->get(route('assessment.start.merchant', $merchant))
            ->assertOk();
        $this->withHeader('X-Inertia', 'true')
            ->get(route('assessment.start'))
            ->assertOk();

        $this->beginAssessment(['participant_name' => 'Peserta Setelah Umum']);

        $test = DiscTest::where('participant_name', 'Peserta Setelah Umum')->firstOrFail();
        $this->assertNull($test->merchant_id);
    }

    public function test_inactive_merchant_returns_404_for_assessment_start_url(): void
    {
        $merchant = Merchant::create(['name' => 'Nonaktif', 'is_active' => false]);

        $this->withoutExceptionHandling();
        $this->expectException(NotFoundHttpException::class);

        $this->get(route('assessment.start.merchant', $merchant));
    }

    public function test_continue_to_ist_keeps_the_merchant_tag_from_the_disc_test(): void
    {
        $this->createQuestionBank();
        $merchant = Merchant::create(['name' => 'Merchant IST', 'is_active' => true]);

        $candidate = User::create([
            'name' => 'Peserta Lanjut IST',
            'email' => 'lanjut-ist@tes-iq.local',
            'password' => bcrypt('secret'),
            'role' => User::ROLE_CANDIDATE,
            'status' => User::STATUS_AKTIF,
            'instansi_id' => $this->instansi()->id,
            'position_id' => $this->position()->id,
        ]);

        DiscTest::create([
            'user_id' => $candidate->id,
            'participant_name' => $candidate->name,
            'age' => 25,
            'gender' => 'L',
            'status' => 'completed',
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($candidate)
            ->withSession(['assessment.biodata' => ['age' => 25, 'gender' => 'L']])
            ->post(route('assessment.continue.ist'));

        $response->assertStatus(303);

        $test = IstTest::where('user_id', $candidate->id)->firstOrFail();
        $this->assertSame($merchant->id, $test->merchant_id);
    }

    public function test_continue_to_competency_derives_department_from_candidate_position(): void
    {
        $candidate = User::create([
            'name' => 'Peserta Lanjut Kompetensi',
            'email' => 'lanjut-kompetensi@tes-iq.local',
            'password' => bcrypt('secret'),
            'role' => User::ROLE_CANDIDATE,
            'status' => User::STATUS_AKTIF,
            'instansi_id' => $this->instansi()->id,
            'position_id' => $this->position()->id,
        ]);

        $istTest = IstTest::create([
            'public_id' => (string) Str::uuid(),
            'user_id' => $candidate->id,
            'participant_name' => $candidate->name,
            'age' => 25,
            'gender' => 'L',
            'status' => IstTest::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($candidate)
            ->withSession(['assessment.biodata' => ['age' => 25, 'gender' => 'L']])
            ->post(route('assessment.continue.competency'));

        $test = CompetencyTest::where('user_id', $candidate->id)->first();
        $this->assertNotNull($test);
        $this->assertSame('sales', $test->department);
        $response->assertRedirect(route('competency.instruction', $test->id));
    }

    public function test_numeric_merchant_id_in_url_is_rejected(): void
    {
        $merchant = Merchant::create(['name' => 'Numeric Id Test', 'is_active' => true]);

        $this->get('/tes/m/'.$merchant->id)->assertNotFound();
    }
}
