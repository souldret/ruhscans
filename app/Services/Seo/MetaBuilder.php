<?php

namespace App\Services\Seo;

use App\Models\Chapter;
use App\Models\Series;
use App\Models\Tag;
use Illuminate\Support\Str;

class MetaBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function home(): array
    {
        return $this->pack(
            title: 'Ruhscans — 18+ manga ve manhwa',
            description: 'Yetişkinlere yönelik Türkçe manga ve manhwa. Bu gece raftaki serileri keşfet.',
            canonical: route('home'),
            schema: [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => config('seo.site_name'),
                'url' => url('/'),
                'inLanguage' => 'tr',
                'isFamilyFriendly' => false,
                'contentRating' => '18+',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function discover(): array
    {
        return $this->pack(
            title: 'Keşfet — manga ve manhwa ara',
            description: 'Tür, durum ve etikete göre 18+ manga ve manhwa ara. Ruhscans gece kütüphanesi.',
            canonical: route('discover'),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function series(Series $series): array
    {
        $type = $series->type->label();
        $title = $series->title.' — '.$type.' oku';
        $description = $series->description
            ? Str::limit(trim(preg_replace('/\s+/', ' ', $series->description) ?? ''), 160)
            : $series->title.' '.$type.' serisini Ruhscans\'ta oku. 18 yaş ve üzeri içerik.';

        return $this->pack(
            title: $title,
            description: $description,
            canonical: route('series.show', $series),
            schema: $this->seriesSchema($series),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function chapter(Series $series, Chapter $chapter): array
    {
        $number = $this->number($chapter->number);
        $title = $series->title.' '.$number.'. bölüm oku';
        $description = $series->title.' serisinin '.$number.'. bölümü. 18+ manga ve manhwa, Ruhscans.';

        $schema = $this->seriesSchema($series);
        $schema['hasPart'] = [
            '@type' => 'ComicIssue',
            'name' => $chapter->title ?: $number.'. bölüm',
            'issueNumber' => $number,
            'url' => route('reader.show', [$series, $chapter->number]),
            'isFamilyFriendly' => false,
        ];

        return $this->pack(
            title: $title,
            description: $description,
            canonical: route('reader.show', [$series, $chapter->number]),
            robots: 'noindex, follow',
            schema: $schema,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function tag(Tag $tag): array
    {
        return $this->pack(
            title: $tag->name.' etiketli manga ve manhwa',
            description: $tag->name.' etiketindeki 18+ seriler. Ruhscans Türkçe manga ve manhwa kütüphanesi.',
            canonical: route('tags.show', $tag),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function privatePage(string $heading, string $description): array
    {
        return $this->pack(
            title: $heading,
            description: $description,
            canonical: url()->current(),
            robots: 'noindex, nofollow',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function legal(string $heading): array
    {
        return $this->pack(
            title: $heading,
            description: $heading.' — Ruhscans yasal bilgilendirme. Site yalnızca 18 yaş ve üzeri okurlar içindir.',
            canonical: url()->current(),
        );
    }

    /**
     * @param  array<string, mixed>|null  $schema
     * @return array<string, mixed>
     */
    private function pack(string $title, string $description, string $canonical, ?string $robots = null, ?array $schema = null): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots ?? (string) config('seo.robots'),
            'image' => asset((string) config('seo.og_image')),
            'schema' => $schema,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seriesSchema(Series $series): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => ['Book', 'ComicSeries'],
            'name' => $series->title,
            'url' => route('series.show', $series),
            'inLanguage' => 'tr',
            'isFamilyFriendly' => false,
            'contentRating' => '18+',
            'author' => $series->author ? ['@type' => 'Person', 'name' => $series->author] : null,
            'genre' => $series->relationLoaded('tags')
                ? $series->tags->pluck('name')->all()
                : [],
        ];
    }

    private function number(mixed $number): string
    {
        $value = (string) $number;

        return str_ends_with($value, '.00') ? substr($value, 0, -3) : rtrim(rtrim($value, '0'), '.');
    }
}
