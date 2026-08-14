<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Models\Notification;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Services\RegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentRevisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_revision_notifies_the_applicant_and_moves_the_registration_back(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin())->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::RevisionRequired->value,
            'verification_note' => 'File ijazah terlalu buram. Silakan upload ulang.',
        ]);

        $registration->refresh();
        $document->refresh();

        $this->assertSame(DocumentStatus::RevisionRequired, $document->verification_status);
        $this->assertSame(RegistrationStatus::RevisionRequired, $registration->registration_status);

        $notification = Notification::query()
            ->where('registration_id', $registration->id)
            ->where('type', Notification::TYPE_DOCUMENT_REVISION)
            ->first();

        $this->assertNotNull($notification);
        $this->assertStringContainsString('File ijazah terlalu buram', $notification->message);
    }

    public function test_the_applicant_sees_the_revision_note_in_the_portal(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin())->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::RevisionRequired->value,
            'verification_note' => 'Kartu keluarga terpotong pada bagian bawah.',
        ]);

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.documents.index'))
            ->assertOk()
            ->assertSee('Kartu keluarga terpotong pada bagian bawah.')
            ->assertSee('Perlu Perbaikan');
    }

    public function test_the_dashboard_warns_about_documents_needing_revision(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin())->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::RevisionRequired->value,
            'verification_note' => 'Perlu unggah ulang.',
        ]);

        $this->actingAsApplicant($registration->fresh())
            ->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Ada berkas yang perlu diperbaiki');
    }

    public function test_re_uploading_resets_the_document_to_pending(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin())->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::RevisionRequired->value,
            'verification_note' => 'Perlu unggah ulang.',
        ]);

        $this->actingAsApplicant($registration->fresh())
            ->post(route('applicant.documents.store', $document->documentType), [
                'file' => UploadedFile::fake()->create('perbaikan.pdf', 180, 'application/pdf'),
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();

        $this->assertSame(DocumentStatus::Pending, $document->verification_status);
        $this->assertNull($document->verification_note);
        $this->assertNull($document->verified_at);
        $this->assertSame('perbaikan.pdf', $document->original_name);
    }

    public function test_the_full_revision_round_trip_ends_in_a_verified_registration(): void
    {
        [$registration, $document] = $this->submitted();
        $admin = $this->createAdmin();

        // 1. Committee asks for a revision.
        $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::RevisionRequired->value,
            'verification_note' => 'Silakan unggah ulang.',
        ]);

        $this->assertSame(RegistrationStatus::RevisionRequired, $registration->fresh()->registration_status);

        // 2. Applicant re-uploads.
        $this->actingAsApplicant($registration->fresh())
            ->post(route('applicant.documents.store', $document->documentType), [
                'file' => UploadedFile::fake()->create('perbaikan.pdf', 180, 'application/pdf'),
            ]);

        // 3. Committee verifies every required document.
        foreach ($registration->fresh()->documents as $doc) {
            $this->actingAs($admin)->post(route('admin.verification.decide', $doc), [
                'verification_status' => DocumentStatus::Verified->value,
            ]);
        }

        // 4. Committee completes verification.
        $this->actingAs($admin)
            ->post(route('admin.verification.complete', $registration))
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertSame(RegistrationStatus::Verified, $registration->registration_status);
        $this->assertNotNull($registration->verified_at);
        $this->assertDatabaseHas('notifications', [
            'registration_id' => $registration->id,
            'type' => Notification::TYPE_VERIFICATION_COMPLETE,
        ]);
    }

    /**
     * @return array{0: Registration, 1: RegistrationDocument}
     */
    private function submitted(): array
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $registration = $registration->fresh();

        return [$registration, $registration->documents()->with('documentType')->firstOrFail()];
    }
}
