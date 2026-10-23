<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $games = Game::query()
            ->published()
            ->with('uploader')
            ->withCount('downloads')
            ->latest('published_at')
            ->limit(12)
            ->get();

        return view('home', [
            'games' => $games,
        ]);
    }
}
