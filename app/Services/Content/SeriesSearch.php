<?php

namespace App\Services\Content;

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Models\Series;
use Illuminate\Database\Eloquent\Builder;

class SeriesSearch
{
    /**
     * @param  array{
     *     q?: string|null,
     *     type?: string|null,
     *     status?: string|null,
     *     tag?: int|string|null,
     *     content_level?: string|null,
     *     sort?: string|null,
     *     include_hidden?: bool
     * }  $filters
     * @return Builder<Series>
     */
    public function query(array $filters = []): Builder
    {
        $query = Series::query()->with('tags');

        if (! ($filters['include_hidden'] ?? false)) {
            $query->where('series.hidden', false);
        }

        if ($type = $filters['type'] ?? null) {
            $query->where('type', SeriesType::from($type));
        }

        if ($status = $filters['status'] ?? null) {
            $query->where('status', SeriesStatus::from($status));
        }

        if ($level = $filters['content_level'] ?? null) {
            $query->where('content_level', ContentLevel::from($level));
        }

        if ($tag = $filters['tag'] ?? null) {
            $query->whereHas('tags', fn (Builder $tags) => $tags->where('tags.id', $tag));
        }

        $term = trim((string) ($filters['q'] ?? ''));

        if ($term !== '') {
            $this->applyTextSearch($query, $term);
        }

        return match ($filters['sort'] ?? 'yeni') {
            'eski' => $query->orderBy('series.created_at'),
            'baslik' => $query->orderBy('series.title'),
            'yil' => $query->orderByDesc('series.year'),
            'trend' => $query->withCount('chapters')->orderByDesc('chapters_count'),
            default => $query->orderByDesc('series.created_at'),
        };
    }

    /**
     * @param  Builder<Series>  $query
     */
    private function applyTextSearch(Builder $query, string $term): void
    {
        $driver = $query->getConnection()->getDriverName();
        $like = '%'.addcslashes($term, '%_\\').'%';

        if ($driver === 'pgsql') {
            $query->where(function (Builder $inner) use ($term, $like) {
                $inner->whereRaw("search_vector @@ plainto_tsquery('simple', ?)", [$term])
                    ->orWhereRaw('similarity(title, ?) > ?', [$term, (float) config('content.search_similarity')])
                    ->orWhereRaw('alternative_names::text ILIKE ?', [$like]);
            });

            return;
        }

        $folded = $this->fold($term);
        $prefix = mb_strlen($folded) >= 4 ? mb_substr($folded, 0, 4) : $folded;
        $like = '%'.addcslashes($prefix, '%_\\').'%';

        $query->where(function (Builder $inner) use ($like) {
            $inner->whereRaw($this->foldedExpression('series.title').' LIKE ?', [$like])
                ->orWhereRaw($this->foldedExpression('series.alternative_names').' LIKE ?', [$like]);
        });
    }

    private function foldedExpression(string $column): string
    {
        $expression = 'lower('.$column.')';
        $map = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u'];

        foreach ($map as $from => $to) {
            $expression = "replace({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }

    private function fold(string $value): string
    {
        $map = [
            'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u',
            'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'I' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u',
        ];

        return mb_strtolower(strtr($value, $map));
    }
}
