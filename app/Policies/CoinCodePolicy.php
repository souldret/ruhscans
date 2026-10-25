<?php

namespace App\Policies;

use App\Models\CoinCode;
use App\Models\User;

class CoinCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CoinCode $code): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CoinCode $code): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, CoinCode $code): bool
    {
        return false;
    }
}
