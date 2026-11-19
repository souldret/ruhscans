<?php

namespace App\Services\Catalog;

use App\Models\Chapter;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Collection;

class HomeCatalog
{
    public function __construct(private readonly CatalogQuery $catalog) {}

    /**
     * @return Collection<int, Series>
     */
    public function showcase(?User $viewer): Collection
    {
        return $this->catalog->query(['sort' => 'yeni'], $viewer)
            ->withCount('chapters')
            ->limit(8)
            ->get();
    }

    /**
     * @return Collection<int, Series>
     */
    public function trending(?User $viewer): Collection
    {
        return $this->catalog->query(['sort' => 'trend'], $viewer)
            ->limit(12)
            ->get();
    }

    /**
     * @return Collection<int, Chapter>
     */
    public function freshChapters(?User $viewer): Collection
    {
        $query = Chapter::query()
            ->with(['series.tags'])
            ->whereNotNull('published_at')
            ->whereHas('series', fn ($series) => $series->where('hidden', false))
            ->latest('published_at')
            ->limit(16);

        $slugs = array_values(array_filter(array_map('strval', $viewer?->etiket_karaliste ?? [])));

        if ($slugs !== []) {
            $query->whereDoesntHave('series.tags', function ($tags) use ($slugs) {
                $tags->whereIn('tags.slug', $slugs);
            });
        }

        return $query->get();
    }

    /**
     * @return Collection<int, Tag>
     */
    public function tagCloud(): Collection
    {
        return Tag::query()
            ->withCount(['series' => fn ($series) => $series->where('hidden', false)])
            ->orderByDesc('series_count')
            ->limit(24)
            ->get();
    }
}
