<?php

namespace App\Services\Catalog;

use App\Models\Chapter;
use App\Models\Series;
use App\Models\User;
use App\Services\Content\SeriesSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CatalogQuery
{
    public function __construct(private readonly SeriesSearch $search) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Series>
     */
    public function query(array $filters = [], ?User $viewer = null): Builder
    {
        $query = $this->search->query($filters);

        if ($viewer !== null) {
            $this->excludeBlacklisted($query, $viewer);
        }

        return $query;
    }

    /**
     * @param  Builder<Series>  $query
     */
    public function excludeBlacklisted(Builder $query, User $viewer): void
    {
        $slugs = array_values(array_filter(
            array_map('strval', $viewer->etiket_karaliste ?? []),
            fn (string $slug) => $slug !== ''
        ));

        if ($slugs === []) {
            return;
        }

        $query->whereDoesntHave('tags', function (Builder $tags) use ($slugs) {
            $tags->whereIn('tags.slug', $slugs);
        });
    }

    /**
     * @param  Collection<int, Series>  $series
     * @return Collection<int, Series>
     */
    public function visible(Collection $series, ?User $viewer): Collection
    {
        $slugs = $this->slugs($viewer);

        if ($slugs === []) {
            return $series->values();
        }

        return $series
            ->filter(fn (Series $item) => $item->tags->pluck('slug')->intersect($slugs)->isEmpty())
            ->values();
    }

    /**
     * @param  Collection<int, Chapter>  $chapters
     * @return Collection<int, Chapter>
     */
    public function visibleChapters(Collection $chapters, ?User $viewer): Collection
    {
        $slugs = $this->slugs($viewer);

        if ($slugs === []) {
            return $chapters->values();
        }

        return $chapters
            ->filter(function (Chapter $chapter) use ($slugs) {
                $tags = $chapter->series?->tags?->pluck('slug') ?? collect();

                return $tags->intersect($slugs)->isEmpty();
            })
            ->values();
    }

    /**
     * @return list<string>
     */
    private function slugs(?User $viewer): array
    {
        return array_values(array_filter(
            array_map('strval', $viewer?->etiket_karaliste ?? []),
            fn (string $slug) => $slug !== ''
        ));
    }
}
