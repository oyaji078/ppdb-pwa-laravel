<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_is_reachable(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Masuk');
    }

    public function test_an_admin_can_sign_in_with_a_username(): void
    {
        $admin = $this->createAdmin();

        $this->post(route('admin.login.store'), [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_an_admin_can_sign_in_with_an_email_address(): void
    {
        $admin = $this->createAdmin();

        $this->post(route('admin.login.store'), [
            'username' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_signing_in_with_a_wrong_password_fails(): void
    {
        $admin = $this->createAdmin();

        $this->from(route('admin.login'))
            ->post(route('admin.login.store'), [
                'username' => $admin->username,
                'password' => 'salah-sekali',
            ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $admin = $this->createAdmin(attributes: ['is_active' => false]);

        $this->post(route('admin.login.store'), [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_an_account_deactivated_mid_session_is_signed_out(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $admin->update(['is_active' => false]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_a_guest_cannot_open_the_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_signing_in_and_out_is_written_to_the_activity_log(): void
    {
        $admin = $this->createAdmin();

        $this->post(route('admin.login.store'), [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityLogger::LOGIN,
        ]);

        $this->post(route('admin.logout'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityLogger::LOGOUT,
        ]);

        $this->assertGuest();
    }

    public function test_signing_in_records_the_last_login_time(): void
    {
        $admin = $this->createAdmin();

        $this->assertNull($admin->last_login_at);

        $this->post(route('admin.login.store'), [
            'username' => $admin->username,
            'password' => 'password',
        ]);

        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    public function test_a_verifier_cannot_reach_configuration_screens(): void
    {
        $verifier = $this->createAdmin(UserRole::Verifier);

        $this->actingAs($verifier)->get(route('admin.academic-years.index'))->assertForbidden();
        $this->actingAs($verifier)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($verifier)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_an_admin_ppdb_cannot_reach_super_admin_screens(): void
    {
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)->get(route('admin.academic-years.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertForbidden();
    }

    public function test_a_super_admin_reaches_every_screen(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)->get(route('admin.academic-years.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.activity-log.index'))->assertOk();
    }

    public function test_admin_pages_are_never_cached(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertHeaderContains('Cache-Control', 'no-store');
    }
}
