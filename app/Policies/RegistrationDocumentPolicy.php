<?php

namespace App\Policies;

use App\Models\RegistrationDocument;
use App\Models\User;

class RegistrationDocumentPolicy
{
    public function view(User $user, RegistrationDocument $document): bool
    {
        return $user->verifiesDocuments();
    }

    public function download(User $user, RegistrationDocument $document): bool
    {
        return $user->verifiesDocuments();
    }

    public function verify(User $user, RegistrationDocument $document): bool
    {
        return $user->verifiesDocuments() && ! $document->registration->isDraft();
    }

    public function delete(User $user, RegistrationDocument $document): bool
    {
        return $user->isSuperAdmin();
    }
}
