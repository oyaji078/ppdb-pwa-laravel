<?php

namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\AccountRegistrationRequest;
use App\Models\AcademicYear;
use App\Models\AdmissionTrack;
use App\Models\Registration;
use App\Models\RegistrationWave;
use App\Services\ApplicantMailer;
use App\Services\RegistrationService;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Step zero: opening the applicant account.
 *
 * The registration number and access code are issued here, so the applicant
 * logs in first and fills the form afterwards. Everything past this point runs
 * behind the applicant session.
 */
class AccountController extends Controller
{
    public function __construct(
        private readonly RegistrationService $registrations,
        private readonly ApplicantSession $session,
        private readonly ApplicantMailer $mailer,
    ) {}

    public function create(): View|RedirectResponse
    {
        if ($this->session->check()) {
            return redirect()->route('registration.resume');
        }

        $year = AcademicYear::current();

        if ($year === null || ! $year->registration_open) {
            return redirect()->route('ppdb.index')
                ->with('warning', 'Pendaftaran sedang tidak dibuka. Silakan periksa jadwal penerimaan.');
        }

        return view('registration.start', [
            'academicYear' => $year,
            'waves' => $year->waves()->open()->orderBy('code')->get(),
            'tracks' => AdmissionTrack::query()
                ->where('academic_year_id', $year->id)->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(AccountRegistrationRequest $request): RedirectResponse
    {
        $year = AcademicYear::current();
        $wave = RegistrationWave::query()->findOrFail($request->integer('registration_wave_id'));
        $track = AdmissionTrack::query()->findOrFail($request->integer('admission_track_id'));

        $registration = $this->registrations->registerAccount(
            $year,
            $wave,
            $track,
            [
                'full_name' => $request->string('full_name')->trim()->value(),
                'nisn' => $request->string('nisn')->trim()->value(),
                'phone' => $request->string('phone')->trim()->value(),
                'email' => $request->string('email')->trim()->value(),
            ],
            $request->string('password')->value(),
        );

        $this->session->login($registration);
        $this->mailer->sendEmailVerification($registration);

        return redirect()->route('registration.biodata')
            ->with('success', sprintf(
                'Akun pendaftaran dibuat. Nomor pendaftaran Anda %s. Silakan lengkapi formulir.',
                $registration->registration_number,
            ));
    }

    /**
     * Confirms the address from the emailed link. Signed and throttled by the
     * route, so the id in the URL cannot be walked.
     */
    public function verifyEmail(Registration $registration): RedirectResponse
    {
        $applicant = $registration->applicant;

        if ($applicant !== null && ! $applicant->hasVerifiedEmail()) {
            $applicant->markEmailAsVerified();
        }

        $target = $this->session->check()
            ? ($registration->isDraft() ? route('registration.resume') : route('applicant.dashboard'))
            : route('login');

        return redirect()->to($target)->with('success', 'Email Anda berhasil diverifikasi.');
    }

    /**
     * Re-sends the verification mail for the logged-in applicant.
     */
    public function resendVerification(Request $request): RedirectResponse
    {
        $registration = $this->session->registration();

        if ($registration === null) {
            return redirect()->route('login');
        }

        if ($registration->applicant?->hasVerifiedEmail()) {
            return back()->with('success', 'Email Anda sudah terverifikasi.');
        }

        $sent = $this->mailer->sendEmailVerification($registration);

        return $sent
            ? back()->with('success', 'Email verifikasi telah dikirim ulang. Periksa kotak masuk Anda.')
            : back()->with('warning', 'Email verifikasi belum dapat dikirim. Hubungi panitia bila masalah berlanjut.');
    }
}
