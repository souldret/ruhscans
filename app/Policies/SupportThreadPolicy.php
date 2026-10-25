<?php

namespace App\Policies;

use App\Enums\SupportThreadStatus;
use App\Models\SupportThread;
use App\Models\User;

class SupportThreadPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupportThread $thread): bool
    {
        return $user->isAdmin() || $user->is($thread->user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function reply(User $user, SupportThread $thread): bool
    {
        if (! $user->isAdmin() && $thread->status === SupportThreadStatus::Closed) {
            return false;
        }

        return $user->isAdmin() || $user->is($thread->user);
    }

    public function close(User $user, SupportThread $thread): bool
    {
        return $thread->status !== SupportThreadStatus::Closed
            && ($user->isAdmin() || $user->is($thread->user));
    }
}
