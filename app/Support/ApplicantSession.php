<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Http\Request;

/**
 * Session-backed authentication for applicants.
 *
 * Applicants have no User record and no password: they prove who they are with
 * a registration number plus access code, and this class keeps that proof in
 * the session. It is deliberately separate from Laravel's auth guards so an
 * applicant session can never satisfy an admin route, or vice versa.
 *
 * Nothing is cached on this object. Laravel caches controller instances on the
 * Route, so anything held here would leak between requests on a long-running
 * server; the current request is resolved on every call instead, and the
 * resolved registration is memoised on that request.
 */
class ApplicantSession
{
    private const REQUEST_CACHE_KEY = 'ppdb.applicant.registration';

    /**
     * Mark the applicant as authenticated for this registration.
     */
    public function login(Registration $registration): void
    {
        $request = $this->request();
        $session = $request->session();

        $session->regenerate();
        $session->put(config('ppdb.applicant_session.key'), $registration->id);
        $session->put(config('ppdb.applicant_session.token_key'), $registration->session_version);

        $request->attributes->set(self::REQUEST_CACHE_KEY, $registration);
    }

    /**
     * The authenticated registration, or null.
     *
     * Returns null when the stored session_version no longer matches the
     * registration — which is how an access-code reset kicks out old sessions.
     */
    public function registration(): ?Registration
    {
        $request = $this->request();

        if ($request->attributes->has(self::REQUEST_CACHE_KEY)) {
            return $request->attributes->get(self::REQUEST_CACHE_KEY);
        }

        $registration = $this->resolve($request);

        $request->attributes->set(self::REQUEST_CACHE_KEY, $registration);

        return $registration;
    }

    public function check(): bool
    {
        return $this->registration() !== null;
    }

    public function id(): ?int
    {
        return $this->registration()?->id;
    }

    public function logout(): void
    {
        $request = $this->request();
        $session = $request->session();

        $session->forget([
            config('ppdb.applicant_session.key'),
            config('ppdb.applicant_session.token_key'),
        ]);

        $session->regenerate();

        $request->attributes->set(self::REQUEST_CACHE_KEY, null);
    }

    private function resolve(Request $request): ?Registration
    {
        $session = $request->session();
        $id = $session->get(config('ppdb.applicant_session.key'));

        if (! $id) {
            return null;
        }

        $registration = Registration::query()
            ->with(['applicant', 'academicYear', 'wave', 'admissionTrack', 'program', 'selectionResult'])
            ->find($id);

        if ($registration === null) {
            $this->logout();

            return null;
        }

        if ((int) $session->get(config('ppdb.applicant_session.token_key')) !== $registration->session_version) {
            $this->logout();

            return null;
        }

        return $registration;
    }

    /**
     * Always the request being handled right now, never one captured earlier.
     */
    private function request(): Request
    {
        return app('request');
    }
}
