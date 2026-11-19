<?php

namespace App\Services\Seo;

use App\Models\Chapter;
use App\Models\Series;
use Illuminate\Support\Carbon;

class SitemapBuilder
{
    public function pageCount(): int
    {
        $catalog = $this->seriesCount() + $this->chapterCount();

        return max(1, (int) ceil($catalog / $this->size()));
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    public function staticUrls(): array
    {
        return [
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('discover'), 'lastmod' => null],
            ['loc' => route('legal.terms'), 'lastmod' => null],
            ['loc' => route('legal.privacy'), 'lastmod' => null],
            ['loc' => route('legal.dmca'), 'lastmod' => null],
            ['loc' => route('legal.age'), 'lastmod' => null],
            ['loc' => route('legal.report'), 'lastmod' => null],
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    public function page(int $page): array
    {
        $size = $this->size();
        $offset = max(0, $page - 1) * $size;
        $seriesTotal = $this->seriesCount();
        $urls = [];

        if ($offset < $seriesTotal) {
            $take = min($size, $seriesTotal - $offset);

            foreach ($this->seriesSlice($offset, $take) as $row) {
                $urls[] = $row;
            }

            $offset = 0;
            $size -= $take;
        } else {
            $offset -= $seriesTotal;
        }

        if ($size > 0) {
            foreach ($this->chapterSlice($offset, $size) as $row) {
                $urls[] = $row;
            }
        }

        return $urls;
    }

    private function seriesCount(): int
    {
        return Series::query()->where('hidden', false)->count();
    }

    private function chapterCount(): int
    {
        return Chapter::query()
            ->whereNotNull('published_at')
            ->whereHas('series', fn ($query) => $query->where('hidden', false))
            ->count();
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function seriesSlice(int $offset, int $limit): array
    {
        return Series::query()
            ->where('hidden', false)
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get(['slug', 'updated_at'])
            ->map(fn (Series $series) => [
                'loc' => route('series.show', $series),
                'lastmod' => $this->stamp($series->updated_at),
            ])
            ->all();
    }

    /**
     * @return list<array{loc: string, lastmod: ?string}>
     */
    private function chapterSlice(int $offset, int $limit): array
    {
        return Chapter::query()
            ->with('series:id,slug,hidden')
            ->whereNotNull('published_at')
            ->whereHas('series', fn ($query) => $query->where('hidden', false))
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get(['series_id', 'number', 'updated_at'])
            ->map(fn (Chapter $chapter) => [
                'loc' => route('reader.show', [$chapter->series, $chapter->number]),
                'lastmod' => $this->stamp($chapter->updated_at),
            ])
            ->all();
    }

    private function size(): int
    {
        return max(1, (int) config('seo.sitemap_per_page'));
    }

    private function stamp(mixed $value): ?string
    {
        if ($value instanceof Carbon) {
            return $value->toAtomString();
        }

        return $value ? Carbon::parse($value)->toAtomString() : null;
    }
}
