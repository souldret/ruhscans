<?php

namespace App\Policies;

use App\Models\LauncherLoginLog;
use App\Models\User;

class LauncherLoginLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, LauncherLoginLog $log): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LauncherLoginLog $log): bool
    {
        return false;
    }

    public function delete(User $user, LauncherLoginLog $log): bool
    {
        return false;
    }
}
