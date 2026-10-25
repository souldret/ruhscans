<?php

namespace App\Policies;

use App\Models\CoinTransaction;
use App\Models\User;

class CoinTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CoinTransaction $transaction): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CoinTransaction $transaction): bool
    {
        return false;
    }

    public function delete(User $user, CoinTransaction $transaction): bool
    {
        return false;
    }
}
