<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guards the traps that made the one shared sign-in unusable.
 *
 * The login itself was never broken; what broke was getting *to* it. A visitor
 * who was still signed in as one account had no way to reach the form, and no
 * way to sign out from a public page, so every attempt looked like a failure.
 */
class LoginFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Laravel's default "already authenticated" target is /dashboard, which does
     * not exist here — the visitor was silently dropped on the home page with no
     * explanation and no way forward.
     */
    public function test_a_signed_in_staff_member_asking_for_the_login_page_lands_on_their_panel(): void
    {
        $this->actingAs($this->createAdmin(UserRole::SuperAdmin))
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_signed_in_applicant_asking_for_the_login_page_lands_on_their_registration(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createAccountFor($config);

        $this->actingAsApplicant($draft)
            ->get(route('login'))
            ->assertRedirect(route('registration.resume'));
    }

    /**
     * Every public page must offer a way out, or a signed-in visitor is stuck
     * with an account they no longer want to be using.
     */
    public function test_public_pages_offer_a_sign_out_when_signed_in(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Keluar')
            ->assertSee('Panel Admin')
            ->assertSee(route('logout'), false);
    }

    public function test_a_guest_still_sees_the_sign_in_and_register_links(): void
    {
        $this->createPpdbConfiguration();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Masuk')
            ->assertSee('Daftar')
            ->assertSee('name="password"', false);
    }

    /**
     * The home page login box must not render a form to someone already signed
     * in: submitting it would just bounce off the guest middleware.
     */
    public function test_the_home_login_box_shows_account_state_instead_of_a_form(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createAccountFor($config);

        $this->actingAsApplicant($draft)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Anda sudah masuk')
            ->assertDontSee('name="password"', false);
    }

    public function test_signing_out_from_a_public_page_returns_to_the_login_form(): void
    {
        $admin = $this->createAdmin(UserRole::SuperAdmin);

        $this->actingAs($admin)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))->assertOk()->assertSee('name="password"', false);
    }

    /**
     * Every staff role signs in through the same form and lands in the panel.
     */
    public function test_every_staff_role_can_sign_in_with_a_username_or_an_email(): void
    {
        foreach ([UserRole::SuperAdmin, UserRole::AdminPpdb, UserRole::Verifier] as $role) {
            $user = $this->createAdmin($role);

            $this->post(route('login.store'), ['email' => $user->username, 'password' => 'password'])
                ->assertRedirect(route('admin.dashboard'));
            $this->post(route('logout'));

            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
                ->assertRedirect(route('admin.dashboard'));
            $this->post(route('logout'));
        }
    }

    public function test_a_deactivated_staff_account_cannot_sign_in(): void
    {
        $admin = $this->createAdmin(UserRole::AdminPpdb, ['is_active' => false]);

        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $admin->username, 'password' => 'password'])
            ->assertSessionHasErrors('email');
    }

    /**
     * A school shares one public IP. Keying the throttle on the address alone
     * would let one person's mistyped password lock out the whole committee.
     */
    public function test_failed_attempts_on_one_account_do_not_lock_out_another(): void
    {
        $victim = $this->createAdmin(UserRole::AdminPpdb);
        $other = $this->createAdmin(UserRole::Verifier);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('login.store'), ['email' => $victim->username, 'password' => 'salah']);
        }

        // The abused account is throttled...
        $this->post(route('login.store'), ['email' => $victim->username, 'password' => 'password'])
            ->assertStatus(429);

        // ...but a different account on the same address still gets through.
        $this->post(route('login.store'), ['email' => $other->username, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }
}
