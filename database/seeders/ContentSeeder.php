<?php

namespace Database\Seeders;

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Models\Chapter;
use App\Models\Page;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TranslatorGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $groups = collect([
            'Gece Atölyesi',
            'Kızıl Mürekkep',
        ])->map(fn (string $name) => TranslatorGroup::query()->firstOrCreate(
            ['slug' => Str::slug($name)],
            ['name' => $name, 'description' => $name.' çeviri ekibi.'],
        ));

        $tags = collect(['Romantik', 'Dram', 'Fantastik', 'Ofis'])->map(
            fn (string $name) => Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name],
            ),
        );

        $catalog = [
            [
                'title' => 'Gece Yarısı Sözleşmesi',
                'alternative_names' => ['Midnight Contract', 'Gece Sozlesmesi'],
                'type' => SeriesType::Manhwa,
                'status' => SeriesStatus::Devam,
                'content_level' => ContentLevel::Acik,
                'year' => 2024,
            ],
            [
                'title' => 'Kırmızı Mürekkep',
                'alternative_names' => ['Red Ink'],
                'type' => SeriesType::Manga,
                'status' => SeriesStatus::Tamamlandi,
                'content_level' => ContentLevel::Yumusak,
                'year' => 2021,
            ],
            [
                'title' => 'Gizli Ofis',
                'alternative_names' => ['Hidden Office', 'Gizli Ofıs'],
                'type' => SeriesType::Webtoon,
                'status' => SeriesStatus::AraVerdi,
                'content_level' => ContentLevel::Acik,
                'year' => 2023,
            ],
        ];

        foreach ($catalog as $index => $item) {
            $series = Series::query()->updateOrCreate(
                ['slug' => Str::slug($item['title'])],
                [
                    'translator_group_id' => $groups[$index % $groups->count()]->id,
                    'title' => $item['title'],
                    'alternative_names' => $item['alternative_names'],
                    'description' => $item['title'].' yetişkin bir dizidir. Tüm karakterler reşittir.',
                    'type' => $item['type'],
                    'status' => $item['status'],
                    'content_level' => $item['content_level'],
                    'year' => $item['year'],
                    'author' => 'Demo Yazar',
                    'artist' => 'Demo Çizer',
                    'hidden' => false,
                ],
            );

            $series->tags()->sync($tags->random(2)->pluck('id'));

            foreach ([1, 2, 10] as $number) {
                $chapter = Chapter::query()->updateOrCreate(
                    ['series_id' => $series->id, 'number' => $number],
                    [
                        'title' => 'Bölüm '.$number,
                        'published_at' => now()->subDays(30 - $number),
                        'ruh_tasi_fiyat' => $number === 10 ? 2 : 0,
                        'vip_ucretsiz' => $number === 10,
                    ],
                );

                if ($chapter->pages()->doesntExist()) {
                    Page::factory()->create([
                        'chapter_id' => $chapter->id,
                        'order' => 1,
                        'r2_path' => 'demo/'.$series->slug.'/'.$number.'/1.webp',
                    ]);
                }
            }
        }
    }
}
