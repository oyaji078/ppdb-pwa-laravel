<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Applicant;
use App\Models\Program;
use App\Models\Registration;
use App\Models\RegistrationWave;
use App\Models\User;
use App\Support\MailConfigurator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the registration lifecycle: draft creation, per-step persistence and
 * the final submit transaction.
 */
class RegistrationService
{
    public function __construct(
        private readonly RegistrationNumberService $numbers,
        private readonly AccessCodeService $accessCodes,
        private readonly PdfService $pdf,
        private readonly DocumentService $documents,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Create the draft that backs the wizard. The applicant record starts
     * empty and is filled in by the biodata step.
     */
    /**
     * Create the applicant account that the registration form is filled in
     * behind.
     *
     * The applicant becomes an ordinary `user` with the applicant role, because
     * everybody in the system signs in through the same form and the role
     * decides where they land.
     *
     * The registration number is issued here rather than at submission time: the
     * applicant needs an account before they can log in and start the form. A
     * number is therefore consumed by every account created, including ones that
     * are never completed — abandoned drafts are cleaned up by the
     * ppdb:prune-drafts command.
     *
     * @param  array{full_name: string, nisn: string, phone: string, email: string}  $identity
     */
    public function registerAccount(
        AcademicYear $year,
        RegistrationWave $wave,
        AdmissionTrack $track,
        array $identity,
        string $password,
    ): Registration {
        return DB::transaction(function () use ($year, $wave, $track, $identity, $password): Registration {
            $applicant = Applicant::query()->create([
                'full_name' => $identity['full_name'],
                'nisn' => $identity['nisn'],
                'phone' => $identity['phone'],
                'email' => $identity['email'],
            ]);

            $user = User::query()->create([
                'username' => null,
                'name' => $identity['full_name'],
                'email' => $identity['email'],
                'password' => $password,
                'role' => UserRole::Applicant,
                'phone' => $identity['phone'],
                'is_active' => true,
            ]);

            $registration = Registration::query()->create([
                'applicant_id' => $applicant->id,
                'user_id' => $user->id,
                'academic_year_id' => $year->id,
                'registration_wave_id' => $wave->id,
                'admission_track_id' => $track->id,
                'registration_status' => RegistrationStatus::Draft,
                'current_step' => 'biodata',
            ]);

            // generate() must run inside this transaction: it holds the counter
            // row with lockForUpdate() so two concurrent sign-ups cannot be
            // handed the same sequence.
            $registration->forceFill([
                'registration_number' => $this->numbers->generate($year, $wave),
            ])->save();

            return $registration->refresh();
        });
    }

    /**
     * The registration an applicant already holds for this year, if any. Used to
     * stop one student opening a second account, and to point them at the login
     * form when they simply forgot they had one.
     */
    public function existingRegistrationForNisn(string $nisn, int $academicYearId): ?Registration
    {
        return Registration::query()
            ->where('academic_year_id', $academicYearId)
            ->whereHas('applicant', fn ($query) => $query->where('nisn', $nisn))
            ->first();
    }

    /**
     * Change the wave/track of an existing draft, e.g. when the applicant goes
     * back to step 1.
     */
    public function updateDraftChoice(Registration $registration, RegistrationWave $wave, AdmissionTrack $track): void
    {
        $registration->update([
            'registration_wave_id' => $wave->id,
            'admission_track_id' => $track->id,
        ]);
    }

    /**
     * Record the furthest step reached so the wizard can resume there.
     */
    public function advanceStep(Registration $registration, string $nextStep): void
    {
        $steps = array_keys(config('ppdb.wizard.steps'));
        $current = array_search($registration->current_step, $steps, true);
        $next = array_search($nextStep, $steps, true);

        if ($next !== false && ($current === false || $next > $current)) {
            $registration->update(['current_step' => $nextStep]);
        }
    }

    /**
     * Everything that must be true before the applicant may press submit.
     *
     * @return array<int, string> Human readable blockers; empty when ready.
     */
    public function submissionBlockers(Registration $registration): array
    {
        $registration->loadMissing([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'documents', 'admissionTrack', 'wave',
        ]);

        $applicant = $registration->applicant;
        $blockers = [];

        if (blank($applicant->full_name) || blank($applicant->nisn) || blank($applicant->birth_date)) {
            $blockers[] = 'Biodata belum lengkap.';
        }

        if ($applicant->address === null || blank($applicant->address->address)) {
            $blockers[] = 'Data alamat belum lengkap.';
        }

        if ($applicant->parentGuardians->isEmpty()) {
            $blockers[] = 'Data orang tua/wali belum diisi.';
        }

        if ($applicant->previousSchool === null || blank($applicant->previousSchool->school_name)) {
            $blockers[] = 'Data asal sekolah belum diisi.';
        }

        if ($registration->program_id === null) {
            $blockers[] = 'Program pilihan belum dipilih.';
        }

        foreach ($registration->missingRequiredDocuments() as $type) {
            $blockers[] = sprintf('Berkas wajib "%s" belum diunggah.', $type->name);
        }

        if (! $registration->wave->isOpen()) {
            $blockers[] = 'Gelombang pendaftaran yang Anda pilih sedang tidak dibuka.';
        }

        // Only when the committee has switched the requirement on, so a school
        // without working SMTP can never be locked out of its own admissions.
        if (app(MailConfigurator::class)->requiresVerification() && ! $applicant->hasVerifiedEmail()) {
            $blockers[] = 'Email Anda belum diverifikasi. Buka tautan verifikasi yang kami kirim, atau kirim ulang dari halaman ini.';
        }

        return $blockers;
    }

    /**
     * Final submit.
     *
     * Runs entirely inside one transaction: status, timestamp and the receipt
     * PDF. The number and access code already exist from account creation, so
     * nothing is issued here.
     *
     * @throws ValidationException
     */
    public function submit(Registration $registration, bool $statementAgreed): void
    {
        if (! $statementAgreed) {
            throw ValidationException::withMessages([
                'statement_agreed' => 'Anda harus menyetujui pernyataan kebenaran data sebelum mengirim pendaftaran.',
            ]);
        }

        if (! $registration->isDraft()) {
            throw ValidationException::withMessages([
                'statement_agreed' => 'Pendaftaran ini sudah pernah dikirim.',
            ]);
        }

        $blockers = $this->submissionBlockers($registration);

        if ($blockers !== []) {
            throw ValidationException::withMessages(['statement_agreed' => $blockers]);
        }

        // The registration number and access code were issued when the account
        // was created, so submission only moves the status forward.
        DB::transaction(function () use ($registration): void {
            $this->assertNotAlreadyRegistered($registration);

            $registration->forceFill([
                'registration_status' => RegistrationStatus::Submitted,
                'statement_agreed' => true,
                'current_step' => 'review',
                'submitted_at' => now(),
            ])->save();

            $registration->refresh()->load(['academicYear', 'wave', 'admissionTrack', 'program']);

            $this->pdf->generateReceipt($registration);
            $this->notifications->registrationSubmitted($registration);
        });

        // The number now exists from account creation, so uploads already land
        // in the right folder and this is a no-op. Kept as a safety net for
        // registrations created before the number moved to sign-up.
        $this->documents->relocateToRegistrationNumber($registration);
    }

    /**
     * Guard against the same student holding two registrations in one year.
     * Checked inside the submit transaction, after the row locks are held.
     *
     * @throws ValidationException
     */
    private function assertNotAlreadyRegistered(Registration $registration): void
    {
        $nisn = $registration->applicant->nisn;

        if (blank($nisn)) {
            return;
        }

        $duplicate = Registration::query()
            ->where('academic_year_id', $registration->academic_year_id)
            ->where('id', '!=', $registration->id)
            ->whereNotNull('submitted_at')
            ->whereHas('applicant', fn ($query) => $query->where('nisn', $nisn))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'statement_agreed' => 'NISN ini sudah terdaftar pada tahun ajaran yang sama.',
            ]);
        }
    }

    /**
     * Whether this NISN is already taken by a submitted registration in the
     * given year. Used by the biodata step so the applicant finds out early.
     */
    public function nisnTaken(string $nisn, int $academicYearId, ?int $exceptRegistrationId = null): bool
    {
        return Registration::query()
            ->where('academic_year_id', $academicYearId)
            ->when($exceptRegistrationId, fn ($query) => $query->where('id', '!=', $exceptRegistrationId))
            ->whereNotNull('submitted_at')
            ->whereHas('applicant', fn ($query) => $query->where('nisn', $nisn))
            ->exists();
    }

    /**
     * Programs offered for a registration's academic year.
     *
     * @return Collection<int, Program>
     */
    public function availablePrograms(Registration $registration): Collection
    {
        return Program::query()
            ->where('academic_year_id', $registration->academic_year_id)
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Remaining seats for a program, or null when it has no quota.
     */
    public function programRemainingQuota(Program $program): ?int
    {
        if ($program->quota === null) {
            return null;
        }

        $taken = $program->registrations()->whereNotNull('submitted_at')->count();

        return max($program->quota - $taken, 0);
    }
}
