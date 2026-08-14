<?php

namespace App\Http\Middleware;

use App\Support\ApplicantSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicantAuthenticated
{
    public function __construct(private readonly ApplicantSession $applicantSession) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->applicantSession->check()) {
            return redirect()
                ->route('status.form')
                ->with('error', 'Sesi Anda telah berakhir. Silakan masuk kembali menggunakan nomor pendaftaran dan kode akses.');
        }

        // Available to controllers and views without re-reading the session.
        $request->attributes->set('registration', $this->applicantSession->registration());

        return $next($request);
    }
}
