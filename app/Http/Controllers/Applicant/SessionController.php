<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Support\ApplicantSession;
use Illuminate\Http\RedirectResponse;

class SessionController extends Controller
{
    public function __construct(private readonly ApplicantSession $session) {}

    public function logout(): RedirectResponse
    {
        $this->session->logout();

        return redirect()->route('status.form')
            ->with('success', 'Anda telah keluar dari portal pendaftar.');
    }
}
