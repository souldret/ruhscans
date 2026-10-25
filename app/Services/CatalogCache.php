<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CatalogCache
{
    /**
     * @return array<string, Collection<int, Game>>
     */
    public function home(): array
    {
        $games = Cache::remember('catalog.home', now()->addMinutes(10), fn () => Game::query()
            ->published()
            ->latest('published_at')
            ->limit(40)
            ->get());

        return [
            'featured' => $games->take(5),
            'newest' => $games->take(10),
            'popular' => $games->sortByDesc('download_count')->take(10)->values(),
            'free' => $games->where('coin_price', 0)->take(10)->values(),
            'updated' => $games->sortByDesc('updated_at')->take(10)->values(),
        ];
    }

    /**
     * @return Collection<int, Tag>
     */
    public function tags(): Collection
    {
        return Cache::remember('catalog.tags', now()->addMinutes(10), fn () => Tag::query()->orderBy('name')->get());
    }

    public static function flush(): void
    {
        Cache::forget('catalog.home');
        Cache::forget('catalog.tags');
    }
}
