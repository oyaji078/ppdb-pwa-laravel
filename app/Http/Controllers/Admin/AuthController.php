<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Accepts either a username or an email address. Rate limited by the
     * throttle:admin-login middleware on the route.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $identifier = $credentials['username'];
        $field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempted = Auth::attempt(
            [$field => $identifier, 'password' => $credentials['password'], 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $attempted) {
            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        Auth::user()->forceFill(['last_login_at' => now()])->save();

        $this->activity->log(ActivityLogger::LOGIN, sprintf('%s masuk ke panel admin.', Auth::user()->name));

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->activity->log(ActivityLogger::LOGOUT, sprintf('%s keluar dari panel admin.', Auth::user()?->name));

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Anda telah keluar.');
    }
}
