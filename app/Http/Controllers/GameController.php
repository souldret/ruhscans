<?php

namespace App\Http\Controllers;

use App\Enums\GameAccess;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $games = Game::query()
            ->where('is_published', true)
            ->with('tags')
            ->when($request->string('q')->isNotEmpty(), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($inner) => $inner
                    ->where('title', 'like', $term)
                    ->orWhere('developer', 'like', $term)
                    ->orWhereHas('tags', fn ($tags) => $tags->where('name', 'like', $term)));
            })
            ->latest('published_at')
            ->paginate(12)
            ->withQueryString();

        return view('games.index', [
            'games' => $games,
            'query' => $request->string('q')->toString(),
        ]);
    }

    public function show(Game $game): View
    {
        abort_unless($game->is_published, 404);

        $game->load(['tags', 'files']);

        return view('games.show', ['game' => $game]);
    }

    public function vip(): View
    {
        $games = Game::query()
            ->where('is_published', true)
            ->where('access', GameAccess::Vip)
            ->with('tags')
            ->latest('published_at')
            ->paginate(12);

        return view('vip', ['games' => $games]);
    }
}
