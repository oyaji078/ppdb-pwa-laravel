<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['username', 'name', 'email', 'password', 'role', 'phone', 'is_active', 'last_login_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Registrations owned by this account. An applicant may register again in a
     * later academic year using the same login.
     *
     * @return HasMany<Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * The registration this applicant is currently working on — the newest one,
     * since an earlier year's registration is history.
     */
    public function currentRegistration(): ?Registration
    {
        if (! $this->isApplicant()) {
            return null;
        }

        return $this->registrations()->latest('id')->first();
    }

    public function isApplicant(): bool
    {
        return $this->role === UserRole::Applicant;
    }

    /**
     * Where this account belongs after signing in, and where it is sent if it
     * asks for the sign-in page while already signed in.
     *
     * Lives on the model because both the login controller and the "already
     * authenticated" redirect need the same answer; sending everyone to "/"
     * instead leaves a signed-in visitor bouncing off the login page with no
     * idea why.
     */
    public function homeUrl(): string
    {
        if (! $this->isApplicant()) {
            return route('admin.dashboard');
        }

        $registration = $this->currentRegistration();

        if ($registration === null) {
            return route('registration.start');
        }

        return $registration->isDraft()
            ? route('registration.resume')
            : route('applicant.dashboard');
    }

    /**
     * Staff reach the admin panel; applicants never do, whatever URL they try.
     */
    public function isStaff(): bool
    {
        return $this->role->isStaff();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Full PPDB management rights: configuration, selection, publishing.
     */
    public function managesPpdb(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::AdminPpdb], true);
    }

    /**
     * May act on documents and registration verification.
     */
    public function verifiesDocuments(): bool
    {
        return in_array($this->role, [UserRole::SuperAdmin, UserRole::AdminPpdb, UserRole::Verifier], true);
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
