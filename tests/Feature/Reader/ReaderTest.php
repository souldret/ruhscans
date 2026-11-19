<?php

use App\Models\Chapter;
use App\Models\ChapterUnlock;
use App\Models\LibraryEntry;
use App\Models\Page;
use App\Models\Series;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

function openChapter(array $chapter = [], int $pages = 2): array
{
    Storage::fake('local');

    $number = $chapter['number'] ?? 1;
    $series = Series::factory()->create(['hidden' => false, 'slug' => 'gece-'.$number]);
    $chapterModel = Chapter::factory()->create(array_merge([
        'series_id' => $series->id,
        'number' => 1,
        'published_at' => now()->subHour(),
        'ruh_tasi_fiyat' => 0,
        'vip_ucretsiz' => false,
    ], $chapter));

    foreach (range(1, $pages) as $order) {
        Storage::disk('local')->put("chapters/{$chapterModel->id}-{$order}.webp", 'webp');
        Page::factory()->create([
            'chapter_id' => $chapterModel->id,
            'order' => $order,
            'r2_path' => "chapters/{$chapterModel->id}-{$order}.webp",
        ]);
    }

    return [$series, $chapterModel];
}

test('yetkisiz istek kilitli bölümde görsel adresi dönmez', function () {
    [$series, $chapter] = openChapter([
        'number' => 4,
        'ruh_tasi_fiyat' => 18,
    ]);

    $path = $chapter->pages()->first()->r2_path;

    $this->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertOk()
        ->assertJsonPath('locked', true)
        ->assertJsonMissingPath('pages')
        ->assertDontSee($path);

    $html = $this->get(route('reader.show', [$series, $chapter->number]))
        ->assertOk()
        ->assertSee('Kilitli bölüm')
        ->assertSee('18 ruh taşı')
        ->assertSee('Kod kullan')
        ->assertSee('VIP seçeneği');

    expect($html->getContent())->not->toContain($path);

    $member = User::factory()->create();

    $this->actingAs($member)
        ->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertOk()
        ->assertJsonPath('locked', true)
        ->assertDontSee($path);
});

test('açık bölüm sayfa listesinde geçici adres döner', function () {
    [$series, $chapter] = openChapter(['number' => 2], 3);

    $this->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertOk()
        ->assertJsonPath('locked', false)
        ->assertJsonCount(3, 'pages')
        ->assertJsonPath('pages.0.order', 1)
        ->assertJsonStructure(['pages' => [['order', 'url', 'width', 'height']]]);
});

test('vip ve açılmış bölüm kilitli sayılmaz', function () {
    [$series, $chapter] = openChapter([
        'number' => 7,
        'ruh_tasi_fiyat' => 9,
        'vip_ucretsiz' => true,
    ]);

    $vip = User::factory()->create(['vip_bitis' => now()->addWeek()]);

    $this->actingAs($vip)
        ->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertJsonPath('locked', false);

    [$pricedSeries, $priced] = openChapter([
        'number' => 8,
        'ruh_tasi_fiyat' => 5,
        'vip_ucretsiz' => false,
    ]);

    $buyer = User::factory()->create();
    ChapterUnlock::query()->create([
        'user_id' => $buyer->id,
        'chapter_id' => $priced->id,
    ]);

    $this->actingAs($buyer)
        ->getJson(route('reader.pages', [$pricedSeries, $priced->number]))
        ->assertJsonPath('locked', false);
});

test('girişli ilerleme kütüphaneye yazılır', function () {
    [$series, $chapter] = openChapter(['number' => 3], 4);
    $user = User::factory()->create(['gizli_mod' => false]);

    $this->actingAs($user)
        ->postJson(route('reader.progress', [$series, $chapter->number]), ['page' => 2])
        ->assertOk()
        ->assertJsonPath('saved', true);

    $entry = LibraryEntry::query()->where('user_id', $user->id)->where('series_id', $series->id)->first();

    expect($entry)->not->toBeNull()
        ->and($entry->last_chapter_id)->toBe($chapter->id)
        ->and($entry->last_page_order)->toBe(2)
        ->and($entry->completed)->toBeFalse()
        ->and($entry->last_read_at)->not->toBeNull();

    $this->actingAs($user)
        ->postJson(route('reader.progress', [$series, $chapter->number]), ['page' => 4])
        ->assertOk();

    expect($entry->fresh()->completed)->toBeTrue();

    $this->actingAs($user)
        ->get(route('reader.history'))
        ->assertOk()
        ->assertSee($series->title)
        ->assertSee('sayfa');
});

test('gizli mod ilerlemeyi kaydetmez', function () {
    [$series, $chapter] = openChapter(['number' => 9], 2);
    $user = User::factory()->create(['gizli_mod' => true]);

    $this->actingAs($user)
        ->postJson(route('reader.progress', [$series, $chapter->number]), ['page' => 1])
        ->assertOk()
        ->assertJsonPath('saved', false)
        ->assertJsonPath('stealth', true);

    expect(LibraryEntry::query()->where('user_id', $user->id)->exists())->toBeFalse();

    $this->actingAs($user)
        ->get(route('reader.show', [$series, $chapter->number]))
        ->assertOk()
 ->assertSee('\u0022stealth\u0022:true', false)
 ->assertSee('Panik');
});

test('okuma modu kullanıcıya yazılır ve kilitli bölümde ilerleme reddedilir', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(route('reader.mode'), ['mode' => 'rtl'])
        ->assertOk()
        ->assertJsonPath('mode', 'rtl');

    expect($user->fresh()->okuma_modu)->toBe('rtl');

    [$series, $chapter] = openChapter([
        'number' => 11,
        'ruh_tasi_fiyat' => 3,
    ]);

    $this->actingAs($user)
        ->postJson(route('reader.progress', [$series, $chapter->number]), ['page' => 1])
        ->assertForbidden();
});
