<?php

namespace App\Services\Catalog;

use App\Models\Chapter;
use App\Models\Series;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CatalogCache
{
    public function __construct(private readonly HomeCatalog $catalog) {}

    /**
     * @return array{showcase: Collection<int, Series>, trending: Collection<int, Series>, fresh: Collection<int, Chapter>, tags: Collection<int, Tag>}
     */
    public function home(): array
    {
        $payload = Cache::remember($this->key('home'), $this->ttl('home'), function () {
            return [
                'showcase' => $this->catalog->showcase(null)->pluck('id')->all(),
                'trending' => $this->catalog->trending(null)->pluck('id')->all(),
                'fresh' => $this->catalog->freshChapters(null)->pluck('id')->all(),
                'tags' => $this->catalog->tagCloud()->pluck('id')->all(),
            ];
        });

        return [
            'showcase' => $this->seriesInOrder($payload['showcase'] ?? []),
            'trending' => $this->seriesInOrder($payload['trending'] ?? []),
            'fresh' => $this->chaptersInOrder($payload['fresh'] ?? []),
            'tags' => $this->tagsInOrder($payload['tags'] ?? []),
        ];
    }

    /**
     * @return Collection<int, Tag>
     */
    public function tags(): Collection
    {
        $ids = Cache::remember($this->key('tags'), $this->ttl('tags'), function () {
            return $this->catalog->tagCloud()->pluck('id')->all();
        });

        return $this->tagsInOrder($ids);
    }

    public function forget(): void
    {
        Cache::forget($this->key('home'));
        Cache::forget($this->key('tags'));
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Series>
     */
    private function seriesInOrder(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $rows = Series::query()
            ->with('tags')
            ->withCount('chapters')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $rows->get($id))->filter()->values();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Chapter>
     */
    private function chaptersInOrder(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $rows = Chapter::query()
            ->with(['series.tags'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $rows->get($id))->filter()->values();
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Tag>
     */
    private function tagsInOrder(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $rows = Tag::query()
            ->withCount(['series' => fn ($series) => $series->where('hidden', false)])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)->map(fn ($id) => $rows->get($id))->filter()->values();
    }

    private function key(string $name): string
    {
        return 'catalog.'.$name;
    }

    private function ttl(string $name): int
    {
        return max(30, (int) config('seo.'.$name.'_ttl', 300));
    }
}
