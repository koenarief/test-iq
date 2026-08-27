<?php

namespace Tests\Feature\Admin;

use App\Models\DiscTest;
use App\Models\Ist\IstTest;
use App\Models\Merchant;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Feature\Ist\Http\IstHttpTestCase;

class MerchantFlowTest extends IstHttpTestCase
{
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

    public function test_edit_page_exposes_merchant_start_urls(): void
    {
        $admin = User::factory()->create();
        $merchant = Merchant::create(['name' => 'Punya URL', 'is_active' => true]);

        $response = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.merchants.edit', $merchant));

        $response->assertOk()
            ->assertJsonPath('props.startUrls.ist', route('ist.index.merchant', $merchant))
            ->assertJsonPath('props.startUrls.disc', route('disc.index.merchant', $merchant));

        $this->assertStringContainsString($merchant->public_id, $response->json('props.startUrls.ist'));
        $this->assertStringNotContainsString('/m/'.$merchant->id, $response->json('props.startUrls.ist'));
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

    public function test_ist_start_via_merchant_url_tags_the_created_test(): void
    {
        $this->createQuestionBank();
        $merchant = Merchant::create(['name' => 'Merchant IST', 'is_active' => true]);

        $this->withHeader('X-Inertia', 'true')
            ->get(route('ist.index.merchant', $merchant))
            ->assertOk();

        $response = $this->post(route('ist.start'), [
            'participant_name' => 'Peserta Merchant',
            'age' => 25,
            'gender' => 'L',
        ]);

        $response->assertStatus(303);

        $test = IstTest::where('participant_name', 'Peserta Merchant')->firstOrFail();
        $this->assertSame($merchant->id, $test->merchant_id);
    }

    public function test_ist_start_without_merchant_url_leaves_merchant_null(): void
    {
        $this->createQuestionBank();

        $this->withHeader('X-Inertia', 'true')
            ->get(route('ist.index'))
            ->assertOk();

        $response = $this->post(route('ist.start'), [
            'participant_name' => 'Peserta Umum IST',
            'age' => 25,
            'gender' => 'L',
        ]);

        $response->assertStatus(303);

        $test = IstTest::where('participant_name', 'Peserta Umum IST')->firstOrFail();
        $this->assertNull($test->merchant_id);
    }

    public function test_ist_merchant_context_does_not_leak_into_a_later_general_start(): void
    {
        $this->createQuestionBank();
        $merchant = Merchant::create(['name' => 'Merchant Bocor', 'is_active' => true]);

        $this->withHeader('X-Inertia', 'true')
            ->get(route('ist.index.merchant', $merchant))
            ->assertOk();
        $this->withHeader('X-Inertia', 'true')
            ->get(route('ist.index'))
            ->assertOk();

        $response = $this->post(route('ist.start'), [
            'participant_name' => 'Peserta Setelah Umum',
            'age' => 25,
            'gender' => 'L',
        ]);

        $response->assertStatus(303);

        $test = IstTest::where('participant_name', 'Peserta Setelah Umum')->firstOrFail();
        $this->assertNull($test->merchant_id);
    }

    public function test_inactive_merchant_returns_404_for_ist_start_url(): void
    {
        $merchant = Merchant::create(['name' => 'Nonaktif', 'is_active' => false]);

        $this->withoutExceptionHandling();
        $this->expectException(NotFoundHttpException::class);

        $this->get(route('ist.index.merchant', $merchant));
    }

    public function test_disc_start_via_merchant_url_tags_the_created_test(): void
    {
        $merchant = Merchant::create(['name' => 'Merchant DISC', 'is_active' => true]);

        $this->withHeader('X-Inertia', 'true')
            ->get(route('disc.index.merchant', $merchant))
            ->assertOk();

        $response = $this->post(route('disc.start'), [
            'participant_name' => 'Peserta DISC Merchant',
            'age' => 25,
            'gender' => 'L',
        ]);

        $test = DiscTest::where('participant_name', 'Peserta DISC Merchant')->firstOrFail();
        $this->assertSame($merchant->id, $test->merchant_id);
        $response->assertRedirect(route('disc.instruction', $test->id));
    }

    public function test_disc_start_without_merchant_url_leaves_merchant_null(): void
    {
        $this->withHeader('X-Inertia', 'true')
            ->get(route('disc.index'))
            ->assertOk();

        $this->post(route('disc.start'), [
            'participant_name' => 'Peserta DISC Umum',
            'age' => 25,
            'gender' => 'L',
        ]);

        $test = DiscTest::where('participant_name', 'Peserta DISC Umum')->firstOrFail();
        $this->assertNull($test->merchant_id);
    }

    public function test_inactive_merchant_returns_404_for_disc_start_url(): void
    {
        $merchant = Merchant::create(['name' => 'DISC Nonaktif', 'is_active' => false]);

        $this->withoutExceptionHandling();
        $this->expectException(NotFoundHttpException::class);

        $this->get(route('disc.index.merchant', $merchant));
    }

    public function test_numeric_merchant_id_in_url_is_rejected(): void
    {
        $merchant = Merchant::create(['name' => 'Numeric Id Test', 'is_active' => true]);

        $this->withoutExceptionHandling();
        $this->expectException(NotFoundHttpException::class);

        $this->get('/ist/m/'.$merchant->id);
    }
}
