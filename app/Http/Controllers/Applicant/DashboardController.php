<?php

namespace App\Http\Controllers\Applicant;

use App\Enums\RegistrationStatus;
use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Registration;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly ApplicantSession $session) {}

    public function index(): View
    {
        $registration = $this->session->registration()->load([
            'applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
            'academicYear', 'wave', 'admissionTrack', 'program',
            'documents.documentType', 'selectionResult', 'reregistration',
        ]);

        return view('applicant.dashboard', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
            'biodataProgress' => $registration->biodataProgress(),
            'documentProgress' => $registration->documentProgress(),
            'documentTypes' => $registration->applicableDocumentTypes(),
            'timeline' => $this->timeline($registration),
            'announcements' => Announcement::query()
                ->visible()
                ->forApplicant($registration->isAccepted() && $registration->hasPublishedResult())
                ->latest('published_at')
                ->limit(4)
                ->get(),
        ]);
    }

    /**
     * Six-stage progress strip. Every state is derived from the database, never
     * from a hardcoded step number.
     *
     * @return array<int, array{label: string, description: string, state: string}>
     */
    private function timeline(Registration $registration): array
    {
        $status = $registration->registration_status;
        $resultPublished = $registration->hasPublishedResult();

        $verificationState = match (true) {
            $status === RegistrationStatus::Verified => 'done',
            $status === RegistrationStatus::RevisionRequired => 'attention',
            in_array($status, [RegistrationStatus::Submitted, RegistrationStatus::UnderReview], true) => 'current',
            default => 'upcoming',
        };

        $selectionState = match (true) {
            $resultPublished => 'done',
            $registration->selectionResult !== null => 'current',
            $status === RegistrationStatus::Verified => 'current',
            default => 'upcoming',
        };

        $resultState = $resultPublished ? 'done' : 'upcoming';

        $reregistrationState = match ($registration->reregistration_status) {
            ReregistrationStatus::Completed => 'done',
            ReregistrationStatus::Pending => 'current',
            ReregistrationStatus::Expired, ReregistrationStatus::Withdrawn => 'attention',
            default => 'upcoming',
        };

        return [
            [
                'label' => 'Pendaftaran',
                'description' => $registration->submitted_at
                    ? 'Terkirim '.$registration->submitted_at->translatedFormat('d M Y')
                    : 'Belum dikirim',
                'state' => $registration->submitted_at ? 'done' : 'current',
            ],
            [
                'label' => 'Biodata & Berkas',
                'description' => sprintf(
                    '%d dari %d berkas wajib terunggah',
                    $registration->documentProgress()['uploaded'],
                    $registration->documentProgress()['total']
                ),
                'state' => $registration->missingRequiredDocuments()->isEmpty() ? 'done' : 'attention',
            ],
            [
                'label' => 'Verifikasi',
                'description' => $status->label(),
                'state' => $verificationState,
            ],
            [
                'label' => 'Seleksi',
                'description' => $status === RegistrationStatus::Verified
                    ? 'Pendaftaran Anda masuk tahap seleksi'
                    : 'Menunggu verifikasi selesai',
                'state' => $selectionState,
            ],
            [
                'label' => 'Hasil',
                'description' => $resultPublished
                    ? $registration->selection_status->label()
                    : 'Hasil seleksi belum diumumkan',
                'state' => $resultState,
            ],
            [
                'label' => 'Daftar Ulang',
                'description' => $registration->selection_status === SelectionStatus::Accepted && $resultPublished
                    ? $registration->reregistration_status->label()
                    : 'Hanya untuk pendaftar yang diterima',
                'state' => $reregistrationState,
            ],
        ];
    }
}
