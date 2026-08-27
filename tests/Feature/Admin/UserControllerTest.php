<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class UserControllerTest extends TestCase
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

    public function test_guest_cannot_access_user_admin(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_list_and_search_users(): void
    {
        $admin = User::factory()->create(['name' => 'Admin One']);
        User::factory()->create(['name' => 'Zebra Person', 'email' => 'zebra@example.com']);

        $response = $this->actingAs($admin)
            ->withHeader('X-Inertia', 'true')
            ->get(route('admin.users.index', ['search' => 'zebra']));

        $response->assertOk()
            ->assertJsonPath('component', 'Admin/Users/Index')
            ->assertJsonPath('props.users.total', 1)
            ->assertJsonPath('props.users.data.0.email', 'zebra@example.com');
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Person',
            'email' => 'new.person@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'verified' => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new.person@example.com')->firstOrFail();
        $this->assertSame('New Person', $user->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_create_requires_password_confirmation_and_unique_email(): void
    {
        $admin = User::factory()->create();
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Dup',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_admin_can_update_user_without_changing_password(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['name' => 'Old Name']);
        $originalPassword = $target->password;

        $response = $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
            'name' => 'Updated Name',
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'verified' => true,
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $target->refresh();
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame($originalPassword, $target->password);
    }

    public function test_admin_can_change_password_on_update(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => 'brandnewpassword',
            'password_confirmation' => 'brandnewpassword',
            'verified' => true,
        ]);

        $this->assertTrue(Hash::check('brandnewpassword', $target->fresh()->password));
    }

    public function test_unverified_checkbox_clears_email_verified_at(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($admin)->put(route('admin.users.update', $target->id), [
            'name' => $target->name,
            'email' => $target->email,
            'password' => '',
            'password_confirmation' => '',
            'verified' => false,
        ]);

        $this->assertNull($target->fresh()->email_verified_at);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create();
        User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin->id));

        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $target->id));

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
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
            throw new RuntimeException('User admin test safety guard failed before connecting.');
        }

        $actualDatabase = DB::selectOne('select database() as active_database')->active_database ?? null;

        if ($actualDatabase !== 'tes_iq_testing' || $actualDatabase === 'tes_iq') {
            throw new RuntimeException('User admin test safety guard rejected the active database.');
        }
    }
}
