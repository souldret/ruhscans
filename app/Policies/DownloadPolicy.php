<?php

namespace App\Policies;

use App\Models\Download;
use App\Models\User;

class DownloadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Download $download): bool
    {
        return $user->isAdmin();
    }
}
