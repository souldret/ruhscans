<?php

namespace App\Services\Catalog;

use App\Models\Series;
use App\Models\User;
use App\Services\Content\SeriesSearch;
use Illuminate\Database\Eloquent\Builder;

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
}
