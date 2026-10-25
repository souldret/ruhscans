<?php

namespace App\Policies;

use App\Models\GameFile;
use App\Models\User;

class GameFilePolicy
{
    public function download(?User $user, GameFile $file): bool
    {
        $game = $file->game;

        if ($game === null || ! $game->is_published) {
            return false;
        }

        if ($game->isFree() || ($user !== null && $user->isAdmin())) {
            return true;
        }

        return $user !== null && $user->unlocks()->where('game_id', $game->id)->exists();
    }
}
