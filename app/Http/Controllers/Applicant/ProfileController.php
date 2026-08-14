<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;

/**
 * Read-only views of the data the applicant submitted.
 *
 * After submission an applicant may not edit their own biodata: corrections go
 * through the committee, which keeps the verified record trustworthy.
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ApplicantSession $session) {}

    public function biodata(): View
    {
        $registration = $this->session->registration()->load('applicant.address');

        return view('applicant.biodata', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
            'address' => $registration->applicant->address,
        ]);
    }

    public function parents(): View
    {
        $registration = $this->session->registration()->load('applicant.parentGuardians');

        return view('applicant.parents', [
            'registration' => $registration,
            'parents' => $registration->applicant->parentGuardians,
        ]);
    }

    public function previousSchool(): View
    {
        $registration = $this->session->registration()->load('applicant.previousSchool');

        return view('applicant.previous-school', [
            'registration' => $registration,
            'school' => $registration->applicant->previousSchool,
        ]);
    }

    public function program(): View
    {
        $registration = $this->session->registration()->load(['program', 'admissionTrack', 'wave', 'academicYear']);

        return view('applicant.program', [
            'registration' => $registration,
        ]);
    }

    public function profile(): View
    {
        $registration = $this->session->registration()->load(['applicant', 'academicYear', 'wave']);

        return view('applicant.profile', [
            'registration' => $registration,
            'applicant' => $registration->applicant,
        ]);
    }
}
