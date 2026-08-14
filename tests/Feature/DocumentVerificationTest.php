<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Services\ActivityLogger;
use App\Services\RegistrationService;
use App\Services\VerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DocumentVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_mark_a_document_verified(): void
    {
        [$registration, $document] = $this->submitted();
        $admin = $this->createAdmin();

        $this->actingAs($admin)
            ->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::Verified->value,
            ])
            ->assertSessionHasNoErrors();

        $document->refresh();

        $this->assertSame(DocumentStatus::Verified, $document->verification_status);
        $this->assertNotNull($document->verified_at);
        $this->assertSame($admin->id, $document->verified_by);
    }

    public function test_verifying_the_first_document_moves_the_registration_under_review(): void
    {
        [$registration, $document] = $this->submitted();

        $this->assertSame(RegistrationStatus::Submitted, $registration->registration_status);

        $this->actingAs($this->createAdmin())
            ->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::Verified->value,
            ]);

        $this->assertSame(RegistrationStatus::UnderReview, $registration->fresh()->registration_status);
    }

    public function test_every_decision_is_written_to_the_verification_log(): void
    {
        [$registration, $document] = $this->submitted();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::RevisionRequired->value,
            'verification_note' => 'Hasil pindai terlalu buram.',
        ]);

        $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::Verified->value,
        ]);

        $logs = $document->fresh()->verificationLogs()->orderBy('id')->get();

        $this->assertCount(2, $logs);
        $this->assertSame(DocumentStatus::Pending, $logs[0]->old_status);
        $this->assertSame(DocumentStatus::RevisionRequired, $logs[0]->new_status);
        $this->assertSame('Hasil pindai terlalu buram.', $logs[0]->note);
        $this->assertSame(DocumentStatus::RevisionRequired, $logs[1]->old_status);
        $this->assertSame(DocumentStatus::Verified, $logs[1]->new_status);
        $this->assertSame($admin->id, $logs[0]->admin_id);
    }

    public function test_a_revision_request_without_a_note_is_rejected(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin())
            ->from(route('admin.verification.show', $registration))
            ->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::RevisionRequired->value,
                'verification_note' => '',
            ])
            ->assertSessionHasErrors('verification_note');

        $this->assertSame(DocumentStatus::Pending, $document->fresh()->verification_status);
    }

    public function test_a_rejection_without_a_note_is_rejected(): void
    {
        [$registration, $document] = $this->submitted();

        $this->expectException(ValidationException::class);

        app(VerificationService::class)->decide(
            $document,
            DocumentStatus::Rejected,
            $this->createAdmin(),
            null
        );
    }

    public function test_verification_is_recorded_in_the_activity_log(): void
    {
        [$registration, $document] = $this->submitted();
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::Verified->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityLogger::DOCUMENT_VERIFIED,
        ]);
    }

    public function test_a_verifier_role_may_decide_documents(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::Verified->value,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(DocumentStatus::Verified, $document->fresh()->verification_status);
    }

    public function test_an_invalid_status_value_is_rejected(): void
    {
        [$registration, $document] = $this->submitted();

        $this->actingAs($this->createAdmin())
            ->from(route('admin.verification.show', $registration))
            ->post(route('admin.verification.decide', $document), [
                'verification_status' => 'diterima-saja',
            ])
            ->assertSessionHasErrors('verification_status');
    }

    public function test_no_notification_is_sent_when_a_document_is_simply_verified(): void
    {
        [$registration, $document] = $this->submitted();

        $before = Notification::query()->where('registration_id', $registration->id)->count();

        $this->actingAs($this->createAdmin())->post(route('admin.verification.decide', $document), [
            'verification_status' => DocumentStatus::Verified->value,
        ]);

        $this->assertSame(
            $before,
            Notification::query()->where('registration_id', $registration->id)->count()
        );
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

        return [$registration, $registration->documents()->firstOrFail()];
    }
}
