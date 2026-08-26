<?php

namespace Tests\Feature;

use App\Services\AccessCodeService;
use App\Services\RegistrationService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\AuthenticateSession;
use Tests\TestCase;

/**
 * Applicant sign-in through the one login shared with staff: e-mail + password.
 */
class AccessCodeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_applicant_signs_in_with_their_email_and_password(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $registration->refresh();

        $this->post(route('login.store'), [
            'email' => $registration->user->email,
            'password' => self::ACCESS_CODE,
        ])->assertRedirect(route('applicant.dashboard'));

        $this->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee($registration->applicant->full_name);
    }

    /**
     * Applicants choose their own password, so the wrong case must not be waved
     * through.
     */
    public function test_the_password_is_case_sensitive(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $registration->fresh()->user->email,
                'password' => strtolower(self::ACCESS_CODE),
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $registration->fresh()->user->email,
                'password' => 'SalahSekali123',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->get(route('applicant.dashboard'))->assertRedirect(route('login'));
    }

    public function test_an_unknown_email_gives_the_same_generic_error(): void
    {
        $this->fakePrivateDisk();
        $this->createPpdbConfiguration();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => 'tidak.ada@example.test',
                'password' => self::ACCESS_CODE,
            ])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);
    }

    public function test_the_portal_requires_a_session(): void
    {
        $this->get(route('applicant.dashboard'))->assertRedirect(route('login'));
        $this->get(route('applicant.documents.index'))->assertRedirect(route('login'));
        $this->get(route('applicant.receipt'))->assertRedirect(route('login'));
    }

    public function test_signing_out_ends_the_session(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->actingAsApplicant($registration->fresh())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('applicant.dashboard'))->assertRedirect(route('login'));
    }

    public function test_resetting_the_password_replaces_the_old_credentials(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);
        $registration->refresh();

        $email = $registration->user->email;

        $this->post(route('login.store'), [
            'email' => $email,
            'password' => self::ACCESS_CODE,
        ])->assertRedirect(route('applicant.dashboard'));

        // Committee resets the password out of band.
        $newCode = app(AccessCodeService::class)->reset($registration->fresh());

        // The login form is behind `guest`, so sign out before trying again —
        // otherwise the attempt is redirected instead of validated.
        $this->post(route('logout'));

        // Old password no longer works, new one does.
        $this->from(route('login'))
            ->post(route('login.store'), ['email' => $email, 'password' => self::ACCESS_CODE])
            ->assertSessionHasErrors('email');

        $this->post(route('login.store'), ['email' => $email, 'password' => $newCode])
            ->assertRedirect(route('applicant.dashboard'));
    }

    /**
     * Sessions opened before a password change are ended by AuthenticateSession,
     * which compares the password hash it stored in the session against the
     * account's current one. That cannot be observed under the array session
     * driver the suite runs on — nothing survives between requests — so what is
     * asserted here is that the middleware is actually in the web stack.
     */
    public function test_the_session_guard_against_changed_passwords_is_registered(): void
    {
        $stack = app(Kernel::class)->getMiddlewareGroups()['web'];

        $this->assertContains(AuthenticateSession::class, $stack);
    }

    /**
     * A draft holds an account from the moment it is opened, and signing in with
     * it resumes the form rather than opening the portal.
     */
    public function test_a_draft_signs_in_and_is_sent_back_to_the_form(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $this->assertNotNull($draft->registration_number);
        $this->assertTrue($draft->isDraft());

        $this->post(route('login.store'), [
            'email' => $draft->user->email,
            'password' => self::ACCESS_CODE,
        ])->assertRedirect(route('registration.resume'));

        // createSubmittableDraft() fills the data directly without walking the
        // steps, so current_step is still the first one.
        $this->get(route('registration.resume'))->assertRedirect(route('registration.biodata'));
    }

    /**
     * RBAC, not a separate login, is what keeps an applicant out of the panel.
     */
    public function test_an_applicant_cannot_reach_the_admin_panel(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);

        $this->actingAsApplicant($registration)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAsApplicant($registration)
            ->get(route('admin.registrations.index'))
            ->assertForbidden();
    }

    /**
     * And the same gate in the other direction: staff have no applicant portal.
     */
    public function test_staff_cannot_reach_the_applicant_portal(): void
    {
        $this->actingAs($this->createAdmin())
            ->get(route('applicant.dashboard'))
            ->assertForbidden();
    }

    public function test_a_deactivated_applicant_cannot_sign_in(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);

        $registration->user->forceFill(['is_active' => false])->save();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $registration->user->email,
                'password' => self::ACCESS_CODE,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_portal_pages_are_never_cached(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.dashboard'))
            ->assertHeaderContains('Cache-Control', 'no-store');
    }
}
