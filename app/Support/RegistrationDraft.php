<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Tracks the in-progress registration for an anonymous visitor.
 *
 * The wizard has no login, so the draft is bound to the browser session. Only
 * a draft owned by the current session can be read or written, which stops one
 * visitor from resuming somebody else's form.
 *
 * Like ApplicantSession, this resolves the current request on every call rather
 * than holding one, because controller instances are cached on the Route and
 * would otherwise carry a stale request (and stale session) between requests.
 */
class RegistrationDraft
{
    private const SESSION_KEY = 'ppdb_draft_registration_id';

    private const SUBMITTED_KEY = 'ppdb_submitted_registration_id';

    public function remember(Registration $registration): void
    {
        $this->request()->session()->put(self::SESSION_KEY, $registration->id);
    }

    /**
     * The current draft, or null when there is none or it is no longer a draft.
     */
    public function current(): ?Registration
    {
        $id = $this->request()->session()->get(self::SESSION_KEY);

        if (! $id) {
            return null;
        }

        $registration = Registration::query()
            ->with(['applicant.address', 'applicant.parentGuardians', 'applicant.previousSchool',
                'academicYear', 'wave', 'admissionTrack', 'program', 'documents.documentType'])
            ->find($id);

        if ($registration === null || ! $registration->isDraft()) {
            $this->forget();

            return null;
        }

        return $registration;
    }

    public function forget(): void
    {
        $this->request()->session()->forget(self::SESSION_KEY);
    }

    /**
     * Remember a just-submitted registration so the success page can be shown
     * once, then reloaded safely.
     */
    public function markSubmitted(Registration $registration): void
    {
        $this->forget();
        $this->request()->session()->put(self::SUBMITTED_KEY, $registration->id);
    }

    public function submitted(): ?Registration
    {
        $id = $this->request()->session()->get(self::SUBMITTED_KEY);

        return $id ? Registration::query()->with(['applicant', 'academicYear', 'wave'])->find($id) : null;
    }

    public function clearSubmitted(): void
    {
        $this->request()->session()->forget(self::SUBMITTED_KEY);
    }

    private function request(): Request
    {
        return app('request');
    }
}
