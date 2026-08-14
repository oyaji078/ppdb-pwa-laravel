<?php

namespace App\Services;

use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Models\Registration;
use App\Models\Reregistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Daftar ulang: only accepted applicants with a published result take part.
 */
class ReregistrationService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * @throws ValidationException
     */
    public function updateStatus(
        Registration $registration,
        ReregistrationStatus $status,
        User $admin,
        ?string $notes = null,
    ): Reregistration {
        if ($registration->selection_status !== SelectionStatus::Accepted || ! $registration->hasPublishedResult()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pendaftar yang dinyatakan diterima dan hasilnya sudah dipublikasikan yang dapat melakukan daftar ulang.',
            ]);
        }

        if ($status === ReregistrationStatus::NotRequired) {
            throw ValidationException::withMessages([
                'status' => 'Status daftar ulang tidak valid.',
            ]);
        }

        $reregistration = DB::transaction(function () use ($registration, $status, $admin, $notes): Reregistration {
            $reregistration = Reregistration::query()->updateOrCreate(
                ['registration_id' => $registration->id],
                [
                    'status' => $status,
                    'notes' => $notes,
                    'started_at' => $registration->reregistration?->started_at ?? now(),
                    'completed_at' => $status === ReregistrationStatus::Completed ? now() : null,
                    'verified_by' => $admin->id,
                ]
            );

            $registration->forceFill([
                'reregistration_status' => $status,
                'reregistered_at' => $status === ReregistrationStatus::Completed ? now() : null,
            ])->save();

            return $reregistration;
        });

        $this->activity->log(
            ActivityLogger::REREGISTRATION_UPDATED,
            sprintf(
                'Daftar ulang %s diubah menjadi %s.',
                $registration->registration_number,
                $status->label()
            ),
            $registration
        );

        if ($status === ReregistrationStatus::Completed) {
            $this->notifications->reregistrationCompleted($registration);
        }

        return $reregistration;
    }
}
