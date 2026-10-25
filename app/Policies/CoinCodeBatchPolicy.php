<?php

namespace App\Policies;

use App\Models\CoinCodeBatch;
use App\Models\User;

class CoinCodeBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CoinCodeBatch $batch): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, CoinCodeBatch $batch): bool
    {
        return false;
    }

    public function delete(User $user, CoinCodeBatch $batch): bool
    {
        return false;
    }
}
