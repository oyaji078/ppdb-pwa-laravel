<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\SelectionStatus;
use App\Enums\UserRole;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Services\SelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SelectionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_registration_cannot_be_selected(): void
    {
        $registration = $this->submitted();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('terverifikasi');

        app(SelectionService::class)->decide(
            $registration,
            SelectionStatus::Accepted,
            $this->createAdmin()
        );
    }

    public function test_a_draft_cannot_jump_straight_to_accepted(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $draft = $this->createSubmittableDraft($config);

        $this->expectException(ValidationException::class);

        app(SelectionService::class)->decide($draft, SelectionStatus::Accepted, $this->createAdmin());

        $this->assertSame(SelectionStatus::Pending, $draft->fresh()->selection_status);
    }

    public function test_a_verifier_cannot_record_a_selection_decision(): void
    {
        $registration = $this->verified();

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->post(route('admin.selection.store', $registration), [
                'status' => SelectionStatus::Accepted->value,
            ])
            ->assertForbidden();

        $this->assertNull($registration->fresh()->selectionResult);
    }

    public function test_a_verifier_cannot_publish_a_result(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)->post(route('admin.selection.store', $registration), [
            'status' => SelectionStatus::Accepted->value,
        ]);

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->post(route('admin.selection.publish', $registration))
            ->assertForbidden();

        $this->assertFalse($registration->fresh()->hasPublishedResult());
    }

    public function test_an_admin_ppdb_can_record_and_publish(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)
            ->post(route('admin.selection.store', $registration), [
                'status' => SelectionStatus::Accepted->value,
                'score' => 87.5,
                'rank' => 3,
                'note' => 'Nilai rapor sangat baik.',
            ])
            ->assertSessionHasNoErrors();

        $result = $registration->fresh()->selectionResult;

        $this->assertSame(SelectionStatus::Accepted, $result->status);
        $this->assertSame('87.50', $result->score);
        $this->assertSame(3, $result->rank);
        $this->assertSame($admin->id, $result->decided_by);
    }

    public function test_pending_is_not_an_assignable_decision(): void
    {
        $registration = $this->verified();

        $this->expectException(ValidationException::class);

        app(SelectionService::class)->decide(
            $registration,
            SelectionStatus::Pending,
            $this->createAdmin(UserRole::AdminPpdb)
        );
    }

    public function test_a_published_result_cannot_be_changed(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin);
        $service->publish($registration->fresh(), $admin);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('sudah dipublikasikan');

        $service->decide($registration->fresh(), SelectionStatus::Rejected, $admin);
    }

    public function test_a_published_result_cannot_be_revoked(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin);
        $service->publish($registration->fresh(), $admin);

        $this->expectException(ValidationException::class);

        $service->revoke($registration->fresh(), $admin);
    }

    public function test_an_unpublished_draft_can_be_revoked(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        app(SelectionService::class)->decide($registration, SelectionStatus::Rejected, $admin);

        $this->assertNotNull($registration->fresh()->selectionResult);

        $this->actingAs($admin)
            ->delete(route('admin.selection.revoke', $registration))
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertNull($registration->selectionResult);
        $this->assertSame(SelectionStatus::Pending, $registration->selection_status);
    }

    private function submitted(): Registration
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        return $registration->fresh();
    }

    private function verified(): Registration
    {
        $registration = $this->submitted();
        $admin = $this->createAdmin();

        foreach ($registration->documents as $document) {
            $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::Verified->value,
            ]);
        }

        $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

        return $registration->fresh();
    }
}
