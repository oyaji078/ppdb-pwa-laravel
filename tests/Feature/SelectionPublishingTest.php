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
use App\Services\SelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionPublishingTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unpublished_result_stays_hidden_from_the_applicant(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        app(SelectionService::class)->decide($registration, SelectionStatus::Accepted, $admin);

        $registration->refresh();

        // The registration's own status must not reveal the decision.
        $this->assertSame(SelectionStatus::Pending, $registration->selection_status);
        $this->assertFalse($registration->hasPublishedResult());

        $this->actingAsApplicant($registration)
            ->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Hasil seleksi belum diumumkan')
            ->assertDontSee('Hasil Seleksi: Diterima');
    }

    public function test_publishing_reveals_the_result_to_the_applicant(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin, note: 'Selamat, Anda diterima.');
        $service->publish($registration->fresh(), $admin);

        $registration->refresh();

        $this->assertSame(SelectionStatus::Accepted, $registration->selection_status);
        $this->assertTrue($registration->hasPublishedResult());

        $this->actingAsApplicant($registration)
            ->get(route('applicant.dashboard'))
            ->assertOk()
            ->assertSee('Hasil Seleksi: Diterima')
            ->assertSee('Selamat, Anda diterima.');
    }

    public function test_publishing_an_acceptance_opens_reregistration(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin);
        $service->publish($registration->fresh(), $admin);

        $registration->refresh();

        $this->assertSame(ReregistrationStatus::Pending, $registration->reregistration_status);
        $this->assertNotNull($registration->reregistration);
        $this->assertSame(ReregistrationStatus::Pending, $registration->reregistration->status);
    }

    public function test_publishing_a_rejection_does_not_open_reregistration(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Rejected, $admin);
        $service->publish($registration->fresh(), $admin);

        $registration->refresh();

        $this->assertSame(ReregistrationStatus::NotRequired, $registration->reregistration_status);
        $this->assertNull($registration->reregistration);
    }

    public function test_publishing_notifies_the_applicant(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin);
        $service->publish($registration->fresh(), $admin);

        $this->assertDatabaseHas('notifications', [
            'registration_id' => $registration->id,
            'type' => Notification::TYPE_SELECTION_PUBLISHED,
        ]);

        $this->assertDatabaseHas('notifications', [
            'registration_id' => $registration->id,
            'type' => Notification::TYPE_REREGISTRATION,
        ]);
    }

    public function test_publishing_is_recorded_in_the_activity_log(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)->post(route('admin.selection.store', $registration), [
            'status' => SelectionStatus::Accepted->value,
        ]);

        $this->actingAs($admin)->post(route('admin.selection.publish', $registration));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityLogger::SELECTION_PUBLISHED,
        ]);
    }

    public function test_publishing_twice_is_harmless(): void
    {
        $registration = $this->verified();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $service->decide($registration, SelectionStatus::Accepted, $admin);
        $first = $service->publish($registration->fresh(), $admin);
        $second = $service->publish($registration->fresh(), $admin);

        $this->assertEquals($first->published_at, $second->published_at);
        $this->assertSame(1, $registration->fresh()->selectionResult()->count());
    }

    public function test_bulk_publishing_covers_every_pending_draft(): void
    {
        $this->fakePrivateDisk();
        $config = $this->createPpdbConfiguration();
        $admin = $this->createAdmin(UserRole::AdminPpdb);
        $service = app(SelectionService::class);

        $registrations = collect(range(1, 3))->map(function () use ($config, $admin, $service) {
            $registration = $this->createSubmittableDraft($config);
            app(RegistrationService::class)->submit($registration, statementAgreed: true);
            $registration = $registration->fresh();

            foreach ($registration->documents as $document) {
                $this->actingAs($admin)->post(route('admin.verification.decide', $document), [
                    'verification_status' => DocumentStatus::Verified->value,
                ]);
            }

            $this->actingAs($admin)->post(route('admin.verification.complete', $registration));

            $registration = $registration->fresh();
            $service->decide($registration, SelectionStatus::Accepted, $admin);

            return $registration;
        });

        $this->actingAs($admin)
            ->post(route('admin.selection.publish-bulk'), ['confirm' => '1'])
            ->assertSessionHasNoErrors();

        foreach ($registrations as $registration) {
            $this->assertTrue($registration->fresh()->hasPublishedResult());
        }

        $this->assertSame(3, Registration::query()->where('selection_status', SelectionStatus::Accepted)->count());
    }

    public function test_bulk_publishing_requires_confirmation(): void
    {
        $admin = $this->createAdmin(UserRole::AdminPpdb);

        $this->actingAs($admin)
            ->from(route('admin.selection.index'))
            ->post(route('admin.selection.publish-bulk'), [])
            ->assertSessionHasErrors('confirm');
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
}
