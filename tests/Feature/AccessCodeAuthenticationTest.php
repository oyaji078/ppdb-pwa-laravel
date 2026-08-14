<?php

namespace Tests\Feature;

use App\Services\AccessCodeService;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessCodeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_applicant_signs_in_with_their_number_and_code(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        $code = app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $registration->refresh();

        $this->post(route('status.authenticate'), [
            'registration_number' => $registration->registration_number,
            'access_code' => $code,
        ])->assertRedirect(route('applicant.dashboard'));

        $this->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee($registration->applicant->full_name);
    }

    public function test_the_access_code_is_accepted_in_lower_case(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        $code = app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->post(route('status.authenticate'), [
            'registration_number' => $registration->fresh()->registration_number,
            'access_code' => strtolower($code),
        ])->assertRedirect(route('applicant.dashboard'));
    }

    public function test_a_wrong_access_code_is_rejected(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->from(route('status.form'))
            ->post(route('status.authenticate'), [
                'registration_number' => $registration->fresh()->registration_number,
                'access_code' => 'ZZZZZZZZ',
            ])
            ->assertRedirect(route('status.form'))
            ->assertSessionHasErrors('registration_number');

        $this->get(route('applicant.dashboard'))->assertRedirect(route('status.form'));
    }

    public function test_an_unknown_registration_number_gives_the_same_generic_error(): void
    {
        $this->fakePrivateDisk();
        $this->createPpdbConfiguration();

        $this->from(route('status.form'))
            ->post(route('status.authenticate'), [
                'registration_number' => '2699999999',
                'access_code' => 'ABCDEFGH',
            ])
            ->assertSessionHasErrors(['registration_number' => 'Nomor pendaftaran atau kode akses tidak sesuai.']);
    }

    public function test_the_portal_requires_a_session(): void
    {
        $this->get(route('applicant.dashboard'))->assertRedirect(route('status.form'));
        $this->get(route('applicant.documents.index'))->assertRedirect(route('status.form'));
        $this->get(route('applicant.receipt'))->assertRedirect(route('status.form'));
    }

    public function test_signing_out_ends_the_session(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $this->actingAsApplicant($registration->fresh())
            ->post(route('applicant.logout'))
            ->assertRedirect(route('status.form'));

        $this->get(route('applicant.dashboard'))->assertRedirect(route('status.form'));
    }

    public function test_resetting_the_access_code_invalidates_the_existing_session(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        $code = app(RegistrationService::class)->submit($registration, statementAgreed: true);
        $registration->refresh();

        // Applicant signs in.
        $this->post(route('status.authenticate'), [
            'registration_number' => $registration->registration_number,
            'access_code' => $code,
        ])->assertRedirect(route('applicant.dashboard'));

        $this->get(route('applicant.dashboard'))->assertOk();

        // Committee resets the code out of band.
        $newCode = app(AccessCodeService::class)->reset($registration->fresh());

        $this->get(route('applicant.dashboard'))->assertRedirect(route('status.form'));

        // Old code no longer works, new one does.
        $this->post(route('status.authenticate'), [
            'registration_number' => $registration->registration_number,
            'access_code' => $code,
        ])->assertSessionHasErrors('registration_number');

        $this->post(route('status.authenticate'), [
            'registration_number' => $registration->registration_number,
            'access_code' => $newCode,
        ])->assertRedirect(route('applicant.dashboard'));
    }

    public function test_a_draft_registration_cannot_be_used_to_sign_in(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $this->assertNull($draft->registration_number);

        $this->post(route('status.authenticate'), [
            'registration_number' => '2601000001',
            'access_code' => 'ABCDEFGH',
        ])->assertSessionHasErrors('registration_number');
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
