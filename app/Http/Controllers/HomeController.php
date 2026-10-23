<?php

namespace App\Http\Controllers;

use App\Enums\GameAccess;
use App\Models\Game;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $published = Game::query()
            ->where('is_published', true)
            ->with(['tags', 'files']);

        $featured = (clone $published)
            ->latest('published_at')
            ->limit(5)
            ->get();

        return view('home', [
            'featured' => $featured,
            'newest' => (clone $published)->latest('published_at')->limit(10)->get(),
            'popular' => (clone $published)->orderByDesc('download_count')->orderByDesc('view_count')->limit(10)->get(),
            'vip' => (clone $published)->where('access', GameAccess::Vip)->latest('published_at')->limit(10)->get(),
            'updated' => (clone $published)->latest('updated_at')->limit(10)->get(),
        ]);
    }
}
