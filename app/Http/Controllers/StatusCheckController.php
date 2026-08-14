<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatusCheckRequest;
use App\Models\Registration;
use App\Services\AccessCodeService;
use App\Support\ApplicantSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Public entry point to the applicant portal: registration number + access code.
 */
class StatusCheckController extends Controller
{
    public function __construct(
        private readonly AccessCodeService $accessCodes,
        private readonly ApplicantSession $session,
    ) {}

    public function form(Request $request): View|RedirectResponse
    {
        if ($this->session->check()) {
            return redirect()->route('applicant.dashboard');
        }

        return view('status.form', [
            // Prefilled when arriving from the receipt QR code.
            'registrationNumber' => preg_replace('/\D+/', '', (string) $request->query('registration')),
        ]);
    }

    /**
     * Rate limited by the throttle:status-check middleware on the route.
     */
    public function authenticate(StatusCheckRequest $request): RedirectResponse
    {
        $registration = Registration::query()
            ->where('registration_number', $request->string('registration_number'))
            ->first();

        // One generic message for both wrong number and wrong code, so the form
        // cannot be used to discover which registration numbers exist.
        $failed = $registration === null
            || $registration->isDraft()
            || ! $this->accessCodes->check($registration, $request->string('access_code'));

        if ($failed) {
            return back()
                ->withInput($request->only('registration_number'))
                ->withErrors(['registration_number' => 'Nomor pendaftaran atau kode akses tidak sesuai.']);
        }

        $this->session->login($registration);

        return redirect()->route('applicant.dashboard');
    }
}
