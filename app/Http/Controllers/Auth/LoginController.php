<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * The one sign-in for the whole system.
 *
 * Applicants, verifiers, PPDB admins and super admins all authenticate here with
 * an e-mail and a password; the role decides where they land and which routes
 * they may reach afterwards. Staff may also type their username, because that is
 * what they are used to and it costs nothing to accept.
 */
class LoginController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function show(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->to($this->destinationFor(Auth::user()));
        }

        return view('auth.login');
    }

    /**
     * Rate limited by the throttle:login middleware on the route.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Email atau nama pengguna wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $identifier = trim($credentials['email']);
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempted = Auth::attempt(
            [$field => $identifier, 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember'),
        );

        if (! $attempted) {
            // One message for both a wrong address and a wrong password, so the
            // form cannot be used to discover which accounts exist.
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->isStaff()) {
            $this->activity->log(ActivityLogger::LOGIN, sprintf('%s masuk ke panel admin.', $user->name));
        }

        return redirect()->intended($this->destinationFor($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user?->isStaff()) {
            $this->activity->log(ActivityLogger::LOGOUT, sprintf('%s keluar dari panel admin.', $user->name));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    /**
     * Where a role belongs after signing in. An applicant still filling the form
     * is returned to the step they reached rather than the portal, which would
     * only show them an empty registration.
     */
    private function destinationFor(User $user): string
    {
        return $user->homeUrl();
    }
}
