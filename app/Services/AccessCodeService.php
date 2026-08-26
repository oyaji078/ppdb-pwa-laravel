<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Hash;

/**
 * Issues and verifies the applicant's sign-in password.
 *
 * Applicants are ordinary `users` now, so the password lives in `users.password`
 * like any other account. This service exists because the committee needs to
 * reset a forgotten one from the registration screen, where a Registration —
 * not a User — is what is on hand.
 *
 * The historical name "access code" survives in the admin wording only; what it
 * refers to is a password the applicant chose themselves.
 */
class AccessCodeService
{
    /**
     * Random temporary password drawn from an alphabet with no 0/O/1/I, so it
     * stays unambiguous on the printed recovery document.
     */
    public function generate(): string
    {
        $alphabet = config('ppdb.access_code.alphabet');
        $length = config('ppdb.access_code.length');
        $max = strlen($alphabet) - 1;

        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Case sensitive: applicants pick their own password, so upper and lower
     * case must be preserved the way any password would be.
     */
    public function check(Registration $registration, string $code): bool
    {
        $user = $registration->user;

        if ($user === null) {
            return false;
        }

        return Hash::check(trim($code), $user->password);
    }

    /**
     * Issue a fresh password for the applicant behind this registration.
     *
     * Changing the password is what ends their existing sessions: the
     * AuthenticateSession middleware keeps the password hash in the session and
     * signs out any session whose copy no longer matches.
     *
     * @return string The new plaintext password — the only time it is available.
     */
    public function reset(Registration $registration): string
    {
        $code = $this->generate();
        $user = $registration->user;

        if ($user === null) {
            return $code;
        }

        // 'password' is a hashed cast, so the plaintext is hashed on save.
        $user->forceFill(['password' => $code])->save();

        return $code;
    }
}
