<?php

namespace App\Policies;

use App\Models\GameUnlock;
use App\Models\User;

class GameUnlockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, GameUnlock $unlock): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, GameUnlock $unlock): bool
    {
        return false;
    }

    public function delete(User $user, GameUnlock $unlock): bool
    {
        return $user->isAdmin();
    }
}
