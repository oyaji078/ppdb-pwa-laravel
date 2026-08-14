<?php

namespace App\Policies;

use App\Models\Registration;
use App\Models\User;

/**
 * Administrator permissions over a registration. Applicants are not User
 * records and never reach these checks.
 */
class RegistrationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->verifiesDocuments();
    }

    public function view(User $user, Registration $registration): bool
    {
        return $user->verifiesDocuments();
    }

    /**
     * Seeing full NIK / family card numbers rather than the masked form.
     */
    public function viewSensitiveData(User $user, Registration $registration): bool
    {
        return $user->managesPpdb();
    }

    public function verify(User $user, Registration $registration): bool
    {
        return $user->verifiesDocuments() && ! $registration->isDraft();
    }

    public function completeVerification(User $user, Registration $registration): bool
    {
        return $user->verifiesDocuments() && ! $registration->isDraft();
    }

    public function reopenVerification(User $user, Registration $registration): bool
    {
        return $user->managesPpdb();
    }

    public function select(User $user, Registration $registration): bool
    {
        return $user->managesPpdb();
    }

    public function publishResult(User $user, Registration $registration): bool
    {
        return $user->managesPpdb();
    }

    public function manageReregistration(User $user, Registration $registration): bool
    {
        return $user->managesPpdb();
    }

    public function resetAccessCode(User $user, Registration $registration): bool
    {
        return $user->managesPpdb() && ! $registration->isDraft();
    }

    public function delete(User $user, Registration $registration): bool
    {
        return $user->isSuperAdmin();
    }

    public function restore(User $user, Registration $registration): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Permanent deletion is reserved for the super admin.
     */
    public function forceDelete(User $user, Registration $registration): bool
    {
        return $user->isSuperAdmin();
    }
}
