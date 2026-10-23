<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Game $game): bool
    {
        return $game->isPublished() || ($user?->isAdmin() ?? false);
    }

    public function download(User $user, Game $game): bool
    {
        return $game->isPublished() && $game->archive_path !== null;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Game $game): bool
    {
        return $user->isAdmin();
    }
}
