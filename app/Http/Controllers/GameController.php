<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterGamesRequest;
use App\Models\Game;
use App\Models\User;
use App\Services\CatalogCache;
use App\Support\GameSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(FilterGamesRequest $request): View
    {
        $filters = $request->validated();
        $tags = $filters['etiket'] ?? [];

        $viewer = $request->user();
        $viewerId = $viewer instanceof User ? $viewer->id : 0;
        $games = Game::query()
            ->published()
            ->with('tags')
            ->when($viewer instanceof User, fn ($query) => $query->withExists([
                'unlocks as owned' => fn ($unlocks) => $unlocks->where('user_id', $viewerId),
            ]))
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
            GameSearch::apply($query, (string) $filters['q']);
        })
            ->when($tags !== [], fn ($query) => $query->whereHas(
                'tags',
                fn ($tagQuery) => $tagQuery->whereIn('slug', $tags),
                '=',
                count($tags),
            ))
            ->when(($filters['erisim'] ?? null) === 'ucretli', fn ($query) => $query->where('coin_price', '>', 0))
            ->when(($filters['erisim'] ?? null) === 'ucretsiz', fn ($query) => $query->where('coin_price', 0))
            ->when(($filters['erisim'] ?? null) === 'aldiklarim', fn ($query) => $query->whereHas(
                'unlocks',
                fn ($unlocks) => $unlocks->where('user_id', $viewerId),
            ))
            ->when(filled($filters['ceviri'] ?? null), fn ($query) => $query->where('translation_status', $filters['ceviri']))
            ->when(($filters['sirala'] ?? 'yeni') === 'populer', fn ($query) => $query->orderByDesc('download_count')->orderByDesc('view_count'))
            ->when(($filters['sirala'] ?? 'yeni') === 'guncellenen', fn ($query) => $query->latest('updated_at'))
            ->when(($filters['sirala'] ?? 'yeni') === 'fiyat-artan', fn ($query) => $query->orderBy('coin_price')->orderByDesc('published_at'))
            ->when(($filters['sirala'] ?? 'yeni') === 'fiyat-azalan', fn ($query) => $query->orderByDesc('coin_price')->orderByDesc('published_at'))
            ->when(! in_array($filters['sirala'] ?? 'yeni', ['populer', 'guncellenen', 'fiyat-artan', 'fiyat-azalan'], true), fn ($query) => $query->latest('published_at'))
            ->paginate(12)
            ->withQueryString();

        return view('games.index', [
            'games' => $games,
            'tags' => app(CatalogCache::class)->tags(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request, Game $game): View
    {
        if (! $game->is_published) {
            $admin = $request->user();
            $preview = $admin instanceof User
                && $admin->isAdmin()
                && $request->hasValidSignature();

            abort_unless($preview, 404);
        }

        $this->authorize('view', $game);

        $game->load([
            'tags',
            'files',
            'reviews' => fn ($query) => $query->with('user')->withCount('votes')->latest(),
        ]);
        $viewer = $request->user();
        $viewerId = $viewer instanceof User ? $viewer->id : 0;
        $game->recordView($viewer instanceof User ? $viewerId : null, $request->ip());

        $relatedQuery = Game::query()
            ->published()
            ->whereKeyNot($game->id)
            ->with('tags')
            ->when($viewer instanceof User, fn ($query) => $query->withExists([
                'unlocks as owned' => fn ($unlocks) => $unlocks->where('user_id', $viewerId),
            ]));

        $tagIds = $game->tags->pluck('id');
        $related = $tagIds->isEmpty()
            ? collect()
            : (clone $relatedQuery)
                ->whereHas(
                    'tags',
                    fn ($query) => $query->whereIn('tags.id', $tagIds),
                )
                ->latest('published_at')
                ->limit(8)
                ->get();

        if ($related->isEmpty()) {
            $price = $game->effectivePrice();
            $band = max(50, (int) round($price * 0.5));
            $low = max(0, $price - $band);
            $high = $price + $band;
            $related = (clone $relatedQuery)
                ->when($price > 0, function ($query) use ($low, $high) {
                    $query->whereRaw(
                        'CASE WHEN sale_price IS NOT NULL AND (sale_ends_at IS NULL OR sale_ends_at > ?) THEN sale_price ELSE coin_price END BETWEEN ? AND ?',
                        [now(), $low, $high],
                    );
                })
                ->latest('updated_at')
                ->limit(8)
                ->get();
        }

        if ($related->isEmpty()) {
            $related = (clone $relatedQuery)->latest('updated_at')->limit(8)->get();
        }

        return view('games.show', [
            'game' => $game,
            'related' => $related,
        ]);
    }
}
