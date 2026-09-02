<?php

namespace Tests\Feature\Merchant;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $manifest = public_path('build/manifest.json');
        if (is_file($manifest)) {
            $this->withHeader('X-Inertia-Version', hash_file('xxh128', $manifest));
        }
    }

    public function test_merchant_dashboard_exposes_scoped_start_urls(): void
    {
        $merchant = Merchant::create(['name' => 'Merchant Dashboard', 'is_active' => true]);
        $user = User::factory()->create([
            'role' => User::ROLE_MERCHANT,
            'merchant_id' => $merchant->id,
        ]);

        $response = $this->actingAs($user)
            ->withHeader('X-Inertia', 'true')
            ->get(route('merchant.dashboard'));

        $response->assertOk()
            ->assertJsonPath('component', 'Merchant/Dashboard')
            ->assertJsonPath('props.startUrls.ist', route('ist.index.merchant', $merchant))
            ->assertJsonPath('props.startUrls.disc', route('disc.index.merchant', $merchant));
    }

    public function test_admin_hitting_merchant_dashboard_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('merchant.dashboard'))
            ->assertRedirect(route('dashboard'));
    }
}
