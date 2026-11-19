<?php

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Models\Series;
use App\Models\Tag;
use App\Services\Content\SeriesSearch;

test('arama başlık ve alternatif adda yazım hatasını tolere eder', function () {
    $match = Series::factory()->create([
        'title' => 'Gece Yarısı Sözleşmesi',
        'alternative_names' => ['Midnight Contract'],
        'hidden' => false,
    ]);
    Series::factory()->create([
        'title' => 'Başka Bir Hikaye',
        'alternative_names' => ['Other Story'],
    ]);

    $byTitle = app(SeriesSearch::class)->query(['q' => 'Gece Yarısı'])->pluck('id');
    $byAlias = app(SeriesSearch::class)->query(['q' => 'Midnite Contract'])->pluck('id');

    expect($byTitle)->toContain($match->id)
        ->and($byAlias)->toContain($match->id)
        ->and($byTitle)->toHaveCount(1);
});

test('gizli seriler ve filtreler aramayı daraltır', function () {
    $tag = Tag::factory()->create();
    $visible = Series::factory()->create([
        'title' => 'Gizli Ofis',
        'type' => SeriesType::Manhwa,
        'status' => SeriesStatus::Devam,
        'content_level' => ContentLevel::Acik,
        'hidden' => false,
    ]);
    $visible->tags()->attach($tag);
    $hidden = Series::factory()->create([
        'title' => 'Gizli Ofis Arsiv',
        'type' => SeriesType::Manhwa,
        'status' => SeriesStatus::Devam,
        'content_level' => ContentLevel::Acik,
        'hidden' => true,
    ]);
    $hidden->tags()->attach($tag);
    Series::factory()->create([
        'title' => 'Gizli Bahce',
        'type' => SeriesType::Manga,
        'status' => SeriesStatus::Tamamlandi,
        'content_level' => ContentLevel::Yumusak,
    ]);

    $ids = app(SeriesSearch::class)->query([
        'q' => 'Gizli',
        'type' => SeriesType::Manhwa->value,
        'status' => SeriesStatus::Devam->value,
        'content_level' => ContentLevel::Acik->value,
        'tag' => $tag->id,
        'sort' => 'baslik',
    ])->pluck('id');

    expect($ids->all())->toBe([$visible->id]);
});
