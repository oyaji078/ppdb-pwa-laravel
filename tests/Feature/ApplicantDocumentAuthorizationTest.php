<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\DocumentType;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ApplicantDocumentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_applicant_cannot_open_another_applicants_document(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $applicantA = $this->createSubmittableDraft($config);
        $service->submit($applicantA, statementAgreed: true);

        $applicantB = $this->createSubmittableDraft($config);
        $service->submit($applicantB, statementAgreed: true);

        $documentOfB = $applicantB->fresh()->documents()->firstOrFail();

        // Applicant A is authenticated, then reaches for B's file.
        $this->actingAsApplicant($applicantA->fresh())
            ->get(route('applicant.documents.preview', $documentOfB))
            ->assertForbidden();

        $this->actingAsApplicant($applicantA->fresh())
            ->get(route('applicant.documents.download', $documentOfB))
            ->assertForbidden();
    }

    public function test_an_applicant_can_open_their_own_document(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $document = $registration->fresh()->documents()->firstOrFail();

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.documents.preview', $document))
            ->assertOk()
            ->assertHeaderContains('Content-Type', 'application/pdf');
    }

    public function test_a_guest_cannot_open_any_document(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $document = $registration->fresh()->documents()->firstOrFail();

        $this->get(route('applicant.documents.preview', $document))
            ->assertRedirect(route('status.form'));
    }

    public function test_an_applicant_cannot_upload_against_another_registration(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $applicantA = $this->createSubmittableDraft($config);
        $service->submit($applicantA, statementAgreed: true);

        $applicantB = $this->createSubmittableDraft($config);
        $service->submit($applicantB, statementAgreed: true);

        $type = $config['documentTypes']['kk'];
        $documentOfB = $applicantB->fresh()->documents()->where('document_type_id', $type->id)->firstOrFail();
        $originalNameOfB = $documentOfB->original_name;

        // The registration is taken from the session, so a forged
        // registration_id in the payload changes nothing.
        $this->actingAsApplicant($applicantA->fresh())
            ->post(route('applicant.documents.store', $type), [
                'file' => UploadedFile::fake()->create('milik-a.pdf', 100, 'application/pdf'),
                'registration_id' => $applicantB->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'milik-a.pdf',
            $applicantA->fresh()->documents()->where('document_type_id', $type->id)->first()->original_name
        );
        $this->assertSame($originalNameOfB, $documentOfB->fresh()->original_name);
    }

    public function test_an_applicant_cannot_replace_a_verified_document(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $type = $config['documentTypes']['kk'];
        $document = $registration->fresh()->documents()->where('document_type_id', $type->id)->firstOrFail();
        $document->forceFill(['verification_status' => DocumentStatus::Verified])->save();

        $this->actingAsApplicant($registration->fresh())
            ->from(route('applicant.documents.index'))
            ->post(route('applicant.documents.store', $type), [
                'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');

        $this->assertSame($document->original_name, $document->fresh()->original_name);
    }

    public function test_an_applicant_can_re_upload_a_document_that_needs_revision(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $type = $config['documentTypes']['kk'];
        $document = $registration->fresh()->documents()->where('document_type_id', $type->id)->firstOrFail();
        $document->forceFill([
            'verification_status' => DocumentStatus::RevisionRequired,
            'verification_note' => 'Hasil pindai terlalu buram.',
        ])->save();

        $this->actingAsApplicant($registration->fresh())
            ->post(route('applicant.documents.store', $type), [
                'file' => UploadedFile::fake()->create('kk-jelas.pdf', 150, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();

        $this->assertSame('kk-jelas.pdf', $document->original_name);
        $this->assertSame(DocumentStatus::Pending, $document->verification_status);
        $this->assertNull($document->verification_note);
    }

    public function test_an_applicant_cannot_upload_a_document_type_outside_their_track(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        // A type that exists but is not attached to this track.
        $foreignType = DocumentType::query()->create([
            'academic_year_id' => $config['year']->id,
            'name' => 'Berkas Jalur Lain',
            'code' => 'jalur-lain',
            'is_required' => true,
            'requires_verification' => true,
            'allowed_extensions' => ['pdf'],
            'max_size_kb' => 2048,
            'is_active' => true,
        ]);

        $this->actingAsApplicant($registration->fresh())
            ->post(route('applicant.documents.store', $foreignType), [
                'file' => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_an_applicant_cannot_read_another_applicants_notification(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $applicantA = $this->createSubmittableDraft($config);
        $service->submit($applicantA, statementAgreed: true);

        $applicantB = $this->createSubmittableDraft($config);
        $service->submit($applicantB, statementAgreed: true);

        $notificationOfB = $applicantB->fresh()->notifications()->firstOrFail();

        $this->actingAsApplicant($applicantA->fresh())
            ->post(route('applicant.notifications.read', $notificationOfB))
            ->assertForbidden();
    }

    public function test_an_applicant_only_downloads_their_own_receipt(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $service = app(RegistrationService::class);

        $applicantA = $this->createSubmittableDraft($config);
        $service->submit($applicantA, statementAgreed: true);

        $applicantB = $this->createSubmittableDraft($config);
        $service->submit($applicantB, statementAgreed: true);

        $response = $this->actingAsApplicant($applicantA->fresh())
            ->get(route('applicant.receipt'))
            ->assertOk();

        $this->assertStringContainsString(
            $applicantA->fresh()->registration_number,
            $response->headers->get('Content-Disposition')
        );
        $this->assertStringNotContainsString(
            $applicantB->fresh()->registration_number,
            $response->headers->get('Content-Disposition')
        );
    }
}
