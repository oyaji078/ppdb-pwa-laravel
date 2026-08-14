<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Registration;
use App\Models\User;
use App\Services\RegistrationService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verification_cannot_be_completed_while_a_required_document_is_unverified(): void
    {
        [$registration] = $this->submitted();

        $blockers = app(VerificationService::class)->verificationBlockers($registration);

        $this->assertNotEmpty($blockers);

        $this->actingAs($this->createAdmin())
            ->from(route('admin.verification.show', $registration))
            ->post(route('admin.verification.complete', $registration))
            ->assertSessionHasErrors('registration');

        $this->assertSame(RegistrationStatus::Submitted, $registration->fresh()->registration_status);
    }

    public function test_verification_cannot_be_completed_while_a_required_document_is_missing(): void
    {
        [$registration, $config] = $this->submitted();

        // Remove one required document entirely.
        $registration->documents()->where('document_type_id', $config['documentTypes']['kk']->id)->delete();

        $blockers = app(VerificationService::class)->verificationBlockers($registration->fresh());

        $this->assertContains('Berkas wajib "Kartu Keluarga" belum diunggah.', $blockers);
    }

    public function test_verification_completes_once_every_required_document_is_verified(): void
    {
        [$registration] = $this->submitted();
        $admin = $this->createAdmin();

        $this->verifyAllDocuments($registration, $admin);

        $this->assertSame([], app(VerificationService::class)->verificationBlockers($registration->fresh()));

        $this->actingAs($admin)
            ->post(route('admin.verification.complete', $registration))
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertSame(RegistrationStatus::Verified, $registration->registration_status);
        $this->assertNotNull($registration->verified_at);
        $this->assertSame($admin->id, $registration->verified_by);
    }

    public function test_an_optional_document_does_not_block_verification(): void
    {
        [$registration, $config] = $this->submitted();
        $admin = $this->createAdmin();

        // The optional certificate was never uploaded.
        $this->assertNull(
            $registration->documents()->where('document_type_id', $config['documentTypes']['sertifikat']->id)->first()
        );

        $this->verifyAllDocuments($registration, $admin);

        $this->assertSame([], app(VerificationService::class)->verificationBlockers($registration->fresh()));
    }

    public function test_incomplete_biodata_blocks_verification(): void
    {
        [$registration] = $this->submitted();
        $admin = $this->createAdmin();

        $this->verifyAllDocuments($registration, $admin);

        $registration->applicant->update(['nisn' => null]);

        $blockers = app(VerificationService::class)->verificationBlockers($registration->fresh());

        $this->assertContains('Biodata pendaftar belum lengkap.', $blockers);
    }

    public function test_a_draft_can_never_be_verified(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $this->expectException(ValidationException::class);

        app(VerificationService::class)->completeVerification($draft, $this->createAdmin());
    }

    public function test_a_verified_registration_can_be_reopened_by_an_admin_ppdb(): void
    {
        [$registration] = $this->submitted();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->verifyAllDocuments($registration, $admin);
        $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

        $this->assertSame(RegistrationStatus::Verified, $registration->fresh()->registration_status);

        $this->actingAs($admin)
            ->post(route('admin.verification.reopen', $registration), [
                'reason' => 'Ada dugaan berkas tidak sesuai.',
            ])
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertSame(RegistrationStatus::UnderReview, $registration->registration_status);
        $this->assertNull($registration->verified_at);
    }

    public function test_a_verifier_cannot_reopen_verification(): void
    {
        [$registration] = $this->submitted();
        $admin = $this->createAdmin();

        $this->verifyAllDocuments($registration, $admin);
        $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->post(route('admin.verification.reopen', $registration), ['reason' => 'Coba buka.'])
            ->assertForbidden();
    }

    public function test_reopening_requires_a_reason(): void
    {
        [$registration] = $this->submitted();
        $admin = $this->createAdmin();

        $this->verifyAllDocuments($registration, $admin);
        $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

        $this->actingAs($admin)
            ->from(route('admin.verification.show', $registration))
            ->post(route('admin.verification.reopen', $registration), ['reason' => ''])
            ->assertSessionHasErrors('reason');
    }

    /**
     * @return array{0: Registration, 1: array<string, mixed>}
     */
    private function submitted(): array
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        return [$registration->fresh(), $config];
    }

    private function verifyAllDocuments(Registration $registration, User $admin): void
    {
        foreach ($registration->fresh()->documents as $document) {
            $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::Verified->value,
            ]);
        }
    }
}
