<?php

namespace App\Services;

use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Models\Registration;
use App\Models\Reregistration;
use App\Models\SelectionResult;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin-driven selection. Decisions are drafted first and only become visible
 * to applicants once explicitly published.
 */
class SelectionService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Record (or update) an unpublished selection decision.
     *
     * @throws ValidationException
     */
    public function decide(
        Registration $registration,
        SelectionStatus $status,
        User $admin,
        ?float $score = null,
        ?int $rank = null,
        ?string $note = null,
    ): SelectionResult {
        if (! $registration->isVerified()) {
            throw ValidationException::withMessages([
                'status' => 'Hanya pendaftaran berstatus terverifikasi yang dapat diseleksi.',
            ]);
        }

        if (! in_array($status, SelectionStatus::decidable(), true)) {
            throw ValidationException::withMessages([
                'status' => 'Status seleksi tidak valid.',
            ]);
        }

        $existing = $registration->selectionResult;

        if ($existing !== null && $existing->isPublished()) {
            throw ValidationException::withMessages([
                'status' => 'Hasil seleksi sudah dipublikasikan dan tidak dapat diubah.',
            ]);
        }

        $result = DB::transaction(function () use ($registration, $status, $admin, $score, $rank, $note): SelectionResult {
            $result = SelectionResult::query()->updateOrCreate(
                ['registration_id' => $registration->id],
                [
                    'status' => $status,
                    'score' => $score,
                    'rank' => $rank,
                    'note' => $note,
                    'decided_by' => $admin->id,
                    'decided_at' => now(),
                ]
            );

            // selection_status on the registration stays "pending" until the
            // result is published, so nothing leaks into applicant views.
            $registration->forceFill(['selected_at' => now()])->save();

            return $result;
        });

        $this->activity->log(
            ActivityLogger::SELECTION_DECIDED,
            sprintf(
                'Hasil seleksi %s ditetapkan sebagai %s (belum dipublikasikan).',
                $registration->registration_number,
                $status->label()
            ),
            $registration
        );

        return $result;
    }

    /**
     * Publish one decision: copy it onto the registration, open reregistration
     * for accepted applicants, and notify.
     *
     * @throws ValidationException
     */
    public function publish(Registration $registration, User $admin): SelectionResult
    {
        $result = $registration->selectionResult;

        if ($result === null) {
            throw ValidationException::withMessages([
                'registration' => 'Pendaftaran ini belum memiliki hasil seleksi.',
            ]);
        }

        if ($result->isPublished()) {
            return $result;
        }

        DB::transaction(function () use ($registration, $result, $admin): void {
            $result->forceFill([
                'published_at' => now(),
                'published_by' => $admin->id,
            ])->save();

            $isAccepted = $result->status === SelectionStatus::Accepted;

            $registration->forceFill([
                'selection_status' => $result->status,
                'reregistration_status' => $isAccepted
                    ? ReregistrationStatus::Pending
                    : ReregistrationStatus::NotRequired,
            ])->save();

            if ($isAccepted) {
                Reregistration::query()->updateOrCreate(
                    ['registration_id' => $registration->id],
                    [
                        'status' => ReregistrationStatus::Pending,
                        'started_at' => now(),
                    ]
                );
            }
        });

        $this->activity->log(
            ActivityLogger::SELECTION_PUBLISHED,
            sprintf('Hasil seleksi %s dipublikasikan.', $registration->registration_number),
            $registration
        );

        $this->notifications->selectionPublished($registration);

        if ($result->status === SelectionStatus::Accepted) {
            $this->notifications->reregistrationOpened($registration);
        }

        return $result->refresh();
    }

    /**
     * Publish every decided-but-unpublished result in a scope.
     *
     * @param  Collection<int, Registration>  $registrations
     * @return int Number published.
     */
    public function publishMany(Collection $registrations, User $admin): int
    {
        $count = 0;

        foreach ($registrations as $registration) {
            if ($registration->selectionResult === null || $registration->selectionResult->isPublished()) {
                continue;
            }

            $this->publish($registration, $admin);
            $count++;
        }

        return $count;
    }

    /**
     * Withdraw an unpublished decision.
     *
     * @throws ValidationException
     */
    public function revoke(Registration $registration, User $admin): void
    {
        $result = $registration->selectionResult;

        if ($result === null) {
            return;
        }

        if ($result->isPublished()) {
            throw ValidationException::withMessages([
                'registration' => 'Hasil yang sudah dipublikasikan tidak dapat dibatalkan.',
            ]);
        }

        DB::transaction(function () use ($registration, $result): void {
            $result->delete();
            $registration->forceFill([
                'selection_status' => SelectionStatus::Pending,
                'selected_at' => null,
            ])->save();
        });

        $this->activity->log(
            ActivityLogger::SELECTION_DECIDED,
            sprintf('Hasil seleksi %s dibatalkan sebelum publikasi.', $registration->registration_number),
            $registration
        );
    }
}
