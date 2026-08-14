<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Registration;
use App\Services\ActivityLogger;
use App\Services\RegistrationService;
use App\Services\ReregistrationService;
use App\Services\SelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReregistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_accepted_applicant_enters_reregistration_on_publication(): void
    {
        $registration = $this->accepted();

        $this->assertSame(ReregistrationStatus::Pending, $registration->reregistration_status);
        $this->assertNotNull($registration->reregistration);
        $this->assertNotNull($registration->reregistration->started_at);
    }

    public function test_an_admin_can_mark_reregistration_complete(): void
    {
        $registration = $this->accepted();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)
            ->post(route('admin.reregistration.update', $registration), [
                'status' => ReregistrationStatus::Completed->value,
                'notes' => 'Berkas daftar ulang lengkap.',
            ])
            ->assertSessionHasNoErrors();

        $registration->refresh();

        $this->assertSame(ReregistrationStatus::Completed, $registration->reregistration_status);
        $this->assertNotNull($registration->reregistered_at);
        $this->assertSame('Berkas daftar ulang lengkap.', $registration->reregistration->notes);
        $this->assertSame($admin->id, $registration->reregistration->verified_by);
    }

    public function test_completion_notifies_the_applicant(): void
    {
        $registration = $this->accepted();

        app(ReregistrationService::class)->updateStatus(
            $registration,
            ReregistrationStatus::Completed,
            $this->createAdmin(UserRole::AdminPpdb)
        );

        $this->assertDatabaseHas('notifications', [
            'registration_id' => $registration->id,
            'type' => Notification::TYPE_REREGISTRATION,
        ]);
    }

    public function test_a_rejected_applicant_cannot_be_moved_into_reregistration(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Rejected, $admin);
        $service->publish($registration->fresh(), $admin);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('diterima');

        app(ReregistrationService::class)->updateStatus(
            $registration->fresh(),
            ReregistrationStatus::Completed,
            $admin
        );
    }

    public function test_an_unpublished_acceptance_cannot_enter_reregistration(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        // Decided but deliberately not published.
        app(SelectionService::class)->decide($registration, SelectionStatus::Accepted, $admin);

        $this->expectException(ValidationException::class);

        app(ReregistrationService::class)->updateStatus(
            $registration->fresh(),
            ReregistrationStatus::Completed,
            $admin
        );
    }

    public function test_a_verifier_cannot_change_reregistration_status(): void
    {
        $registration = $this->accepted();

        $this->actingAs($this->createAdmin(UserRole::Verifier))
            ->post(route('admin.reregistration.update', $registration), [
                'status' => ReregistrationStatus::Completed->value,
            ])
            ->assertForbidden();

        $this->assertSame(ReregistrationStatus::Pending, $registration->fresh()->reregistration_status);
    }

    public function test_an_applicant_may_withdraw(): void
    {
        $registration = $this->accepted();

        app(ReregistrationService::class)->updateStatus(
            $registration,
            ReregistrationStatus::Withdrawn,
            $this->createAdmin(UserRole::AdminPpdb),
            'Peserta memilih sekolah lain.'
        );

        $registration->refresh();

        $this->assertSame(ReregistrationStatus::Withdrawn, $registration->reregistration_status);
        $this->assertNull($registration->reregistered_at);
    }

    public function test_the_change_is_written_to_the_activity_log(): void
    {
        $registration = $this->accepted();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)->post(route('admin.reregistration.update', $registration), [
            'status' => ReregistrationStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityLogger::REREGISTRATION_UPDATED,
        ]);
    }

    public function test_the_applicant_sees_their_reregistration_status(): void
    {
        $registration = $this->accepted();

        $this->actingAsApplicant($registration)
            ->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Daftar Ulang');
    }

    public function test_only_accepted_applicants_appear_on_the_reregistration_screen(): void
    {
        $accepted = $this->accepted();

        $rejected = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);
        $service->decide($rejected, SelectionStatus::Rejected, $admin);
        $service->publish($rejected->fresh(), $admin);

        // Drop flash messages left by the setup requests so the assertions see
        // the table only.
        $this->flushSession();

        $this->actingAs($admin)
            ->get(route('admin.reregistration.index'))
            ->assertOk()
            ->assertSee($accepted->registration_number)
            ->assertDontSee($rejected->fresh()->registration_number);
    }

    private function verified(): Registration
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $registration = $this->createSubmittableDraft($config);
        app(RegistrationService::class)->submit($registration, statementAgreed: true);

        $registration = $registration->fresh();
        $admin = $this->createAdmin();

        foreach ($registration->documents as $document) {
            $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
                'verification_status' => DocumentStatus::Verified->value,
            ]);
        }

        $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

        return $registration->fresh();
    }

    private function accepted(): Registration
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin);
        $service->publish($registration->fresh(), $admin);

        return $registration->fresh();
    }
}
