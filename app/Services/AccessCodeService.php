<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Hash;

/**
 * Generates and verifies applicant access codes.
 *
 * Only the hash is ever persisted. The plaintext code exists once, at the
 * moment of creation, and is shown to the applicant a single time.
 */
class AccessCodeService
{
    /**
     * Random code drawn from an alphabet with no 0/O/1/I, so it stays
     * unambiguous on a printed receipt.
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

    public function hash(string $code): string
    {
        return Hash::make(strtoupper($code));
    }

    public function check(Registration $registration, string $code): bool
    {
        if ($registration->access_code_hash === null) {
            return false;
        }

        return Hash::check(strtoupper(trim($code)), $registration->access_code_hash);
    }

    /**
     * Issue a fresh code, invalidating every existing applicant session for
     * this registration.
     *
     * @return string The new plaintext code — the only time it is available.
     */
    public function reset(Registration $registration): string
    {
        $code = $this->generate();

        $registration->forceFill([
            'access_code_hash' => $this->hash($code),
            'session_version' => $registration->session_version + 1,
        ])->save();

        return $code;
    }
}
