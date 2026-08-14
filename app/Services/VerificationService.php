<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Models\DocumentVerificationLog;
use App\Models\Registration;
use App\Models\RegistrationDocument;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Document verification and the registration-level "finish verification" gate.
 */
class VerificationService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly ActivityLogger $activity,
    ) {}

    /**
     * Apply a verification decision to one document, writing an audit log row
     * and notifying the applicant when action is needed.
     *
     * @throws ValidationException
     */
    public function decide(
        RegistrationDocument $document,
        DocumentStatus $status,
        User $admin,
        ?string $note = null,
    ): RegistrationDocument {
        $needsNote = in_array($status, [DocumentStatus::RevisionRequired, DocumentStatus::Rejected], true);

        if ($needsNote && blank($note)) {
            throw ValidationException::withMessages([
                'verification_note' => 'Catatan wajib diisi ketika meminta perbaikan atau menolak berkas.',
            ]);
        }

        $previous = $document->verification_status;

        DB::transaction(function () use ($document, $status, $admin, $note, $previous): void {
            $document->forceFill([
                'verification_status' => $status,
                'verification_note' => $note,
                'verified_at' => $status === DocumentStatus::Verified ? now() : null,
                'verified_by' => $admin->id,
            ])->save();

            DocumentVerificationLog::query()->create([
                'registration_document_id' => $document->id,
                'admin_id' => $admin->id,
                'old_status' => $previous,
                'new_status' => $status,
                'note' => $note,
            ]);

            $this->syncRegistrationStatus($document->registration, $status);
        });

        $this->activity->log(
            ActivityLogger::DOCUMENT_VERIFIED,
            sprintf(
                'Berkas "%s" milik %s diubah dari %s menjadi %s.',
                $document->documentType->name,
                $document->registration->registration_number ?? '-',
                $previous->label(),
                $status->label()
            ),
            $document
        );

        $this->notifyApplicant($document, $status, $note);

        return $document->refresh();
    }

    /**
     * Move the registration along as documents are decided. A registration
     * under review that gets a revision request drops back to
     * revision_required; the first decision on a submitted registration moves
     * it to under_review.
     */
    private function syncRegistrationStatus(Registration $registration, DocumentStatus $status): void
    {
        $current = $registration->registration_status;

        if (in_array($status, [DocumentStatus::RevisionRequired, DocumentStatus::Rejected], true)) {
            if ($current !== RegistrationStatus::RevisionRequired) {
                $registration->forceFill([
                    'registration_status' => RegistrationStatus::RevisionRequired,
                    'verified_at' => null,
                    'verified_by' => null,
                ])->save();
            }

            return;
        }

        if ($current === RegistrationStatus::Submitted) {
            $registration->forceFill(['registration_status' => RegistrationStatus::UnderReview])->save();
        }
    }

    private function notifyApplicant(RegistrationDocument $document, DocumentStatus $status, ?string $note): void
    {
        $name = $document->documentType->name;

        match ($status) {
            DocumentStatus::RevisionRequired => $this->notifications
                ->documentRevisionRequired($document->registration, $name, (string) $note),
            DocumentStatus::Rejected => $this->notifications
                ->documentRejected($document->registration, $name, (string) $note),
            default => null,
        };
    }

    /**
     * Reasons a registration cannot be marked verified yet.
     *
     * @return array<int, string>
     */
    public function verificationBlockers(Registration $registration): array
    {
        $registration->loadMissing([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'documents.documentType', 'admissionTrack',
        ]);

        $blockers = [];
        $applicant = $registration->applicant;

        if (blank($applicant->nisn) || blank($applicant->full_name) || blank($applicant->birth_date)) {
            $blockers[] = 'Biodata pendaftar belum lengkap.';
        }

        if ($applicant->address === null || blank($applicant->address->address)) {
            $blockers[] = 'Alamat pendaftar belum lengkap.';
        }

        if ($applicant->parentGuardians->isEmpty()) {
            $blockers[] = 'Data orang tua/wali belum diisi.';
        }

        if ($applicant->previousSchool === null) {
            $blockers[] = 'Data asal sekolah belum diisi.';
        }

        if ($registration->program_id === null) {
            $blockers[] = 'Program pilihan belum dipilih.';
        }

        foreach ($registration->missingRequiredDocuments() as $type) {
            $blockers[] = sprintf('Berkas wajib "%s" belum diunggah.', $type->name);
        }

        foreach ($registration->unverifiedRequiredDocuments() as $type) {
            if ($registration->documents->firstWhere('document_type_id', $type->id) !== null) {
                $blockers[] = sprintf('Berkas wajib "%s" belum diverifikasi.', $type->name);
            }
        }

        return array_values(array_unique($blockers));
    }

    /**
     * Final check, then flip the registration to verified.
     *
     * @throws ValidationException
     */
    public function completeVerification(Registration $registration, User $admin): Registration
    {
        if ($registration->isDraft()) {
            throw ValidationException::withMessages([
                'registration' => 'Pendaftaran masih berstatus draft dan belum dikirim.',
            ]);
        }

        $blockers = $this->verificationBlockers($registration);

        if ($blockers !== []) {
            throw ValidationException::withMessages(['registration' => $blockers]);
        }

        $registration->forceFill([
            'registration_status' => RegistrationStatus::Verified,
            'verified_at' => now(),
            'verified_by' => $admin->id,
        ])->save();

        $this->activity->log(
            ActivityLogger::REGISTRATION_STATUS_CHANGED,
            sprintf('Pendaftaran %s dinyatakan terverifikasi.', $registration->registration_number),
            $registration
        );

        $this->notifications->verificationCompleted($registration);

        return $registration->refresh();
    }

    /**
     * Send a verified registration back for another look.
     */
    public function reopenVerification(Registration $registration, User $admin, string $reason): Registration
    {
        if (! $registration->isVerified()) {
            throw ValidationException::withMessages([
                'registration' => 'Hanya pendaftaran terverifikasi yang dapat dibuka kembali.',
            ]);
        }

        if ($registration->selectionResult !== null) {
            throw ValidationException::withMessages([
                'registration' => 'Pendaftaran ini sudah memiliki hasil seleksi dan tidak dapat dibuka kembali.',
            ]);
        }

        $registration->forceFill([
            'registration_status' => RegistrationStatus::UnderReview,
            'verified_at' => null,
            'verified_by' => null,
        ])->save();

        $this->activity->log(
            ActivityLogger::REGISTRATION_STATUS_CHANGED,
            sprintf('Verifikasi pendaftaran %s dibuka kembali. Alasan: %s', $registration->registration_number, $reason),
            $registration
        );

        return $registration->refresh();
    }
}
