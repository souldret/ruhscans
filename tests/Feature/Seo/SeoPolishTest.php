<?php

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Models\Chapter;
use App\Models\CodeBatch;
use App\Models\LibraryEntry;
use App\Models\RedeemCode;
use App\Models\Report;
use App\Models\Series;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Catalog\CatalogCache;
use App\Services\Ops\AdminDigest;
use App\Services\Ops\SiteOperations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

test('sitemap gizli seriyi dışlar ve yayınlı bölümü sayfalar', function () {
    config(['seo.sitemap_per_page' => 2]);

    $visible = Series::factory()->create(['slug' => 'acik-gece', 'hidden' => false]);
    Chapter::factory()->create([
        'series_id' => $visible->id,
        'number' => 1,
        'published_at' => now(),
    ]);

    $hidden = Series::factory()->create(['slug' => 'gizli-raf', 'hidden' => true]);
    Chapter::factory()->create([
        'series_id' => $hidden->id,
        'number' => 9,
        'published_at' => now(),
    ]);

    $draft = Series::factory()->create(['slug' => 'taslak-seri', 'hidden' => false]);
    Chapter::factory()->create([
        'series_id' => $draft->id,
        'number' => 4,
        'published_at' => null,
    ]);

    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('content-type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /admin')
        ->assertSee('Disallow: /cuzdan')
        ->assertSee('Sitemap: '.url('/sitemap.xml'));

    $index = $this->get('/sitemap.xml')->assertOk();
    $index->assertHeader('content-type', 'application/xml; charset=UTF-8');
    expect($index->getContent())->toContain('<sitemapindex')
        ->and($index->getContent())->toContain('/sitemap-1.xml');

    $page = $this->get('/sitemap-1.xml')->assertOk();
    expect($page->getContent())->toContain('/seri/acik-gece')
        ->and($page->getContent())->toContain('/seri/taslak-seri')
        ->and($page->getContent())->not->toContain('gizli-raf')
        ->and($page->getContent())->not->toContain('/bolum/4')
        ->and($page->getContent())->not->toContain('/bolum/9');

    $next = $this->get('/sitemap-2.xml')->assertOk();
    expect($next->getContent())->toContain('/seri/acik-gece/bolum/1')
        ->and($next->getContent())->not->toContain('gizli-raf')
        ->and($next->getContent())->not->toContain('/bolum/4')
        ->and($next->getContent())->not->toContain('/bolum/9');

    $this->get('/sitemap-99.xml')->assertNotFound();
});

test('seri sayfası nötr og görseli, canonical ve comic şema taşır', function () {
    $series = Series::factory()->create([
        'title' => 'Gece Sözleşmesi',
        'slug' => 'gece-sozlesmesi',
        'cover_path' => 'covers/adult.webp',
        'hidden' => false,
        'author' => 'Ada Yazar',
    ]);
    $chapter = Chapter::factory()->create([
        'series_id' => $series->id,
        'number' => 2,
        'published_at' => now(),
    ]);

    $this->get(route('series.show', $series))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('series.show', $series).'">', false)
        ->assertSee('og:image" content="'.asset('og-adult.svg').'"', false)
        ->assertDontSee('og:image" content="'.asset('storage/covers/adult.webp').'"', false)
        ->assertSee('ComicSeries', false)
        ->assertSee('isFamilyFriendly":false', false)
        ->assertSee('18+', false);

    $this->get(route('discover'))
        ->assertOk()
        ->assertSee('Keşfet — manga ve manhwa ara', false)
        ->assertSee('rel="canonical"', false);

    $this->get(route('reader.show', [$series, $chapter->number]))
        ->assertOk()
        ->assertSee('noindex, follow', false)
        ->assertSee('ComicIssue', false);

    $this->get(route('legal.age'))
        ->assertOk()
        ->assertSee('yalnızca 18 yaş ve üzeri okurlar içindir', false);
});

test('katalog önbelleği kara listeyi bozmaz ve kayıtta unutulur', function () {
    $series = Series::factory()->create(['title' => 'Önbellek Gece', 'hidden' => false]);
    Chapter::factory()->create(['series_id' => $series->id, 'published_at' => now()]);

    $this->get(route('home'))->assertOk()->assertSee('Önbellek Gece');
    expect(Cache::has('catalog.home'))->toBeTrue();

    $series->update(['title' => 'Yenilenen Gece']);

    expect(Cache::has('catalog.home'))->toBeFalse();

    $this->get(route('home'))->assertOk()->assertSee('Yenilenen Gece');
    app(CatalogCache::class)->forget();
});

test('yönetici özeti bugünkü okuma üye kod ve bekleyen şikayeti sayar', function () {
    Cache::flush();

    $reader = User::factory()->create();
    $series = Series::factory()->create();
    $chapter = Chapter::factory()->create(['series_id' => $series->id]);

    LibraryEntry::query()->create([
        'user_id' => $reader->id,
        'series_id' => $series->id,
        'last_chapter_id' => $chapter->id,
        'last_read_at' => now(),
    ]);

    $batch = CodeBatch::query()->create([
        'ad' => 'Test',
        'tur' => CodeKind::RuhTasi,
        'deger' => 10,
        'adet' => 1,
    ]);

    RedeemCode::query()->create([
        'batch_id' => $batch->id,
        'tur' => CodeKind::RuhTasi,
        'deger' => 10,
        'durum' => CodeStatus::Kullanildi,
        'kod_hash' => hash('sha256', 'test'),
        'son_dort' => 'TEST',
        'kullanim_tarihi' => now(),
    ]);

    Report::factory()->create();

    $digest = app(AdminDigest::class)->today();

    expect($digest['readings'])->toBe(1)
        ->and($digest['members'])->toBeGreaterThanOrEqual(1)
        ->and($digest['codes'])->toBe(1)
        ->and($digest['reports'])->toBe(1);
});

test('site ayarları panik adresi sonteklif ve duyuruyu kaydeder', function () {
    Artisan::shouldReceive('call')->andReturn(0);

    $ops = app(SiteOperations::class);
    SiteSetting::put('panic_url', 'https://ornek.test/panik');
    SiteSetting::put('sonteklif_url', 'https://ornek.test/kod');
    SiteSetting::put('announcement', 'Bu gece bakım var.');

    expect($ops->panicUrl())->toBe('https://ornek.test/panik')
        ->and($ops->sonteklifUrl())->toBe('https://ornek.test/kod')
        ->and($ops->announcement())->toBe('Bu gece bakım var.');

    $ops->enableMaintenance();
    expect($ops->maintenance())->toBeTrue();

    $ops->disableMaintenance();
    expect($ops->maintenance())->toBeFalse();

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Bu gece bakım var.');
});

test('hata sayfaları 18+ uyarı taşır', function () {
    $this->get('/olmayan-raf')->assertNotFound()->assertSee('18 yaş ve üzeri');

    $this->get(route('library'))->assertRedirect();

    $html = view('errors.403', [])->render();
    expect($html)->toContain('18 yaş');

    $down = view('errors.500')->render();
    expect($down)->toContain('18+');

    $care = view('errors.503')->render();
    expect($care)->toContain('18 yaş ve üzeri');
});
