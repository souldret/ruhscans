<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameFavorite;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameFavoriteController extends Controller
{
    public function store(Request $request, Game $game): RedirectResponse
    {
        abort_unless($game->is_published, 404);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $existing = GameFavorite::query()
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->first();

        if ($existing instanceof GameFavorite) {
            $existing->delete();

            return back()->with('favorite', 'Favorilerden çıkarıldı.');
        }

        GameFavorite::query()->create([
            'user_id' => $user->id,
            'game_id' => $game->id,
        ]);

        return back()->with('favorite', 'Favorilere eklendi.');
    }
}
