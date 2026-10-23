<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(): View
    {
        $games = Game::query()
            ->published()
            ->with('uploader')
            ->withCount('downloads')
            ->latest('published_at')
            ->paginate(12);

        return view('games.index', [
            'games' => $games,
        ]);
    }

    public function show(Game $game): View
    {
        $this->authorize('view', $game);

        $game->load('uploader')->loadCount('downloads');

        return view('games.show', [
            'game' => $game,
        ]);
    }
}
