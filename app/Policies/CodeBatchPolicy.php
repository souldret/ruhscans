<?php

namespace App\Policies;

use App\Models\CodeBatch;
use App\Models\User;

class CodeBatchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CodeBatch $batch): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CodeBatch $batch): bool
    {
        return false;
    }

    public function delete(User $user, CodeBatch $batch): bool
    {
        return false;
    }
}
