<?php

namespace App\Policies;

use App\Models\LauncherDevice;
use App\Models\User;

class LauncherDevicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, LauncherDevice $device): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, LauncherDevice $device): bool
    {
        return false;
    }

    public function delete(User $user, LauncherDevice $device): bool
    {
        return $user->isAdmin();
    }
}
