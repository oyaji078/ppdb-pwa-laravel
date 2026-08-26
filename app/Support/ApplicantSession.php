<?php

namespace App\Support;

use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the registration belonging to whoever is signed in.
 *
 * Applicants used to have a bespoke session of their own. They are ordinary
 * `users` now — everybody signs in through one form and the role decides where
 * they land — so this is a thin reader over the auth guard rather than a second
 * authentication system. It survives as a named concept because "the
 * registration the current applicant is working on" is asked for all over the
 * portal and the form.
 *
 * Nothing is cached on the object itself. Laravel caches controller instances on
 * the Route, so anything held here would leak between requests on a long-running
 * server; the resolved registration is memoised on the request instead.
 */
class ApplicantSession
{
    private const REQUEST_CACHE_KEY = 'ppdb.applicant.registration';

    /**
     * Sign in as the applicant who owns this registration.
     *
     * Used after opening an account, and by tests that need a signed-in
     * applicant without walking the login form.
     */
    public function login(Registration $registration): void
    {
        $user = $registration->user;

        if ($user === null) {
            return;
        }

        $request = $this->request();

        Auth::login($user);
        $request->session()->regenerate();

        $request->attributes->set(self::REQUEST_CACHE_KEY, $registration);
    }

    /**
     * The signed-in applicant's registration, or null when nobody is signed in
     * or the account is staff rather than an applicant.
     */
    public function registration(): ?Registration
    {
        $request = $this->request();

        if ($request->attributes->has(self::REQUEST_CACHE_KEY)) {
            return $request->attributes->get(self::REQUEST_CACHE_KEY);
        }

        $registration = $this->resolve();

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

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $request->attributes->set(self::REQUEST_CACHE_KEY, null);
    }

    private function resolve(): ?Registration
    {
        $user = Auth::user();

        if ($user === null || ! $user->isApplicant() || ! $user->is_active) {
            return null;
        }

        return Registration::query()
            ->with(['applicant', 'academicYear', 'wave', 'admissionTrack', 'program', 'selectionResult'])
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->first();
    }

    /**
     * Always the request being handled right now, never one captured earlier.
     */
    private function request(): Request
    {
        return app('request');
    }
}
