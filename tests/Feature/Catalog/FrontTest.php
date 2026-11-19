<?php

use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Livewire\CoverBlurToggle;
use App\Livewire\DiscoverCatalog;
use App\Models\Chapter;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

test('kara listedeki etiket keşif ve ana sayfada gizlenir', function () {
    $blocked = Tag::factory()->create(['name' => 'Yasak', 'slug' => 'yasak']);
    $open = Tag::factory()->create(['name' => 'Acik', 'slug' => 'acik-etiket']);

    $hidden = Series::factory()->create(['title' => 'Yasak Gece', 'hidden' => false]);
    $hidden->tags()->attach($blocked);
    Chapter::factory()->create(['series_id' => $hidden->id, 'published_at' => now()]);

    $visible = Series::factory()->create(['title' => 'Serbest Gece', 'hidden' => false]);
    $visible->tags()->attach($open);
    Chapter::factory()->create(['series_id' => $visible->id, 'published_at' => now()]);

    $user = User::factory()->create(['etiket_karaliste' => ['yasak']]);

    $this->actingAs($user)
        ->get(route('discover'))
        ->assertOk()
        ->assertSee('Serbest Gece')
        ->assertDontSee('Yasak Gece');

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Serbest Gece')
        ->assertDontSee('Yasak Gece');

    $this->actingAs($user)
        ->get(route('tags.show', $blocked))
        ->assertOk()
        ->assertDontSee('Yasak Gece');
});

test('kapak bulanıklığı tercihi kaydedilir', function () {
    $user = User::factory()->create(['kapak_blur' => true]);

    Livewire::actingAs($user)
        ->test(CoverBlurToggle::class)
        ->call('setBlur', false)
        ->assertSet('blur', false);

    expect($user->fresh()->kapak_blur)->toBeFalse();

    $this->actingAs($user->fresh())
        ->get(route('home'))
        ->assertOk()
        ->assertSee('\\u0022blur\\u0022:false', false);
});

test('keşif anlık arama ve filtre uygular', function () {
    Series::factory()->create([
        'title' => 'Gece Yarısı Sözleşmesi',
        'type' => SeriesType::Manhwa,
        'status' => SeriesStatus::Devam,
        'hidden' => false,
    ]);
    Series::factory()->create([
        'title' => 'Sabah Çayı',
        'type' => SeriesType::Manga,
        'hidden' => false,
    ]);

    Livewire::test(DiscoverCatalog::class)
        ->set('q', 'Gece')
        ->assertSee('Gece Yarısı Sözleşmesi')
        ->assertDontSee('Sabah Çayı')
        ->set('type', SeriesType::Manga->value)
        ->assertDontSee('Gece Yarısı Sözleşmesi');
});

test('kütüphane ve seri sayfası oturumla açılır', function () {
    $series = Series::factory()->create(['title' => 'Kilitli Oda', 'hidden' => false]);
    Chapter::factory()->create([
        'series_id' => $series->id,
        'number' => 3,
        'ruh_tasi_fiyat' => 12,
        'vip_ucretsiz' => true,
    ]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('series.show', $series))
        ->assertOk()
        ->assertSee('Kilitli Oda')
        ->assertSee('VIP')
        ->assertSee('12 taş');

    $this->actingAs($user)->get(route('library'))->assertOk();
    auth()->logout();
    $this->get(route('library'))->assertRedirect(route('login'));
});
