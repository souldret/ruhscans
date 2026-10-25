<?php

namespace App\Policies;

use App\Models\CodeAttempt;
use App\Models\User;

class CodeAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CodeAttempt $attempt): bool
    {
        return $user->isAdmin();
    }
}
