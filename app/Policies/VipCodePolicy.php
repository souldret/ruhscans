<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VipCode;

class VipCodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, VipCode $code): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, VipCode $code): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, VipCode $code): bool
    {
        return false;
    }
}
