<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                // Applicant accounts are created by registering and are
                // managed from the registration screens, not here.
                ->whereNot('role', UserRole::Applicant)
                ->when($request->filled('q'), fn ($q) => $q->where(
                    fn ($query) => $query->whereLike('name', '%'.$request->string('q').'%')
                        ->orWhereLike('username', '%'.$request->string('q').'%')
                        ->orWhereLike('email', '%'.$request->string('q').'%')
                ))
                ->when($request->filled('role'), fn ($q) => $q->where('role', $request->string('role')))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString(),
            'roles' => UserRole::staffOptions(),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => UserRole::Verifier, 'is_active' => true]),
            'roles' => UserRole::staffOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:25'],
            // only(): an admin account screen must not be able to mint applicant
            // logins, which belong to a registration.
            'role' => ['required', Rule::enum(UserRole::class)->only(UserRole::staffCases())],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $user = User::query()->create($validated);

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Akun admin %s (%s) dibuat.', $user->name, $user->role->label()),
            $user
        );

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil dibuat.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'user' => $user,
            'roles' => UserRole::staffOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->ignore($user)],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:25'],
            // only(): an admin account screen must not be able to mint applicant
            // logins, which belong to a registration.
            'role' => ['required', Rule::enum(UserRole::class)->only(UserRole::staffCases())],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        // Never let an admin lock themselves out or demote their own account.
        if ($user->id === $request->user()->id) {
            $validated['role'] = $user->role;
            $validated['is_active'] = true;
        } else {
            $validated['is_active'] = $request->boolean('is_active');
        }

        if (blank($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        $this->activity->log(
            ActivityLogger::CONFIGURATION_CHANGED,
            sprintf('Akun admin %s diperbarui.', $user->name),
            $user
        );

        return redirect()->route('admin.users.index')->with('success', 'Akun admin berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->isSuperAdmin() && User::query()->where('role', UserRole::SuperAdmin)->count() <= 1) {
            return back()->with('error', 'Super admin terakhir tidak dapat dihapus.');
        }

        $name = $user->name;
        $user->delete();

        $this->activity->log(ActivityLogger::CONFIGURATION_CHANGED, sprintf('Akun admin %s dihapus.', $name));

        return redirect()->route('admin.users.index')->with('success', 'Akun admin dihapus.');
    }
}
