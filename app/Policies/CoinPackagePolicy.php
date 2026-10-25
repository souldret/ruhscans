<?php

namespace App\Policies;

use App\Models\CoinPackage;
use App\Models\User;

class CoinPackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CoinPackage $package): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, CoinPackage $package): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, CoinPackage $package): bool
    {
        return $user->isAdmin();
    }
}
