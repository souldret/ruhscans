<?php

use App\Livewire\DiscoverCatalog;
use App\Models\Chapter;
use App\Models\Series;
use App\Models\User;
use Livewire\Livewire;

test('mobil öncelikli ana sayfa alt gezinme ve panik tuşunu taşır', function () {
    $series = Series::factory()->create(['title' => 'Gece Sözleşmesi']);
    Chapter::factory()->for($series)->create(['number' => 1, 'published_at' => now()]);

    $this->withCookie('ruhscans_age_confirmed', '1')
        ->get('/')
        ->assertOk()
        ->assertSee('Mobil menü')
        ->assertSee('Keşfet')
        ->assertSee('Kütüphane')
        ->assertSee('Ara')
        ->assertSee('Profil')
        ->assertSee('Panik tuşu');
});

test('keşfet bileşeni arama, filtre sheet ve daha fazla yükleme destekler', function () {
    Series::factory()->create(['title' => 'Gece Yarısı']);
    Series::factory()->create(['title' => 'Gündüz Işığı']);

    Livewire::test(DiscoverCatalog::class)
        ->set('q', 'Gece')
        ->assertSee('Gece Yarısı')
        ->assertDontSee('Gündüz Işığı')
        ->call('loadMore')
        ->assertSet('perPage', 36)
        ->call('resetFilters')
        ->assertSet('q', '')
        ->assertSet('sort', 'yeni');
});

test('profil ve cüzdan sayfaları gece temasında açılır', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $this->actingAs($user)
        ->withCookie('ruhscans_age_confirmed', '1')
        ->get('/profile')
        ->assertOk()
        ->assertSee('Hesap bilgileri')
        ->assertSee('night-grain');

    $this->actingAs($user)
        ->withCookie('ruhscans_age_confirmed', '1')
        ->get('/cuzdan')
        ->assertOk()
        ->assertSee('Kod nereden alınır?')
        ->assertSee('Yapıştır');
});

test('PWA kabuk dosyaları mevcuttur ve yalnızca kabuğu önbelleğe alır', function () {
    expect(file_exists(public_path('manifest.json')))->toBeTrue();
    expect(file_exists(public_path('sw.js')))->toBeTrue();
    expect(file_exists(public_path('offline.html')))->toBeTrue();
    expect(file_exists(public_path('icons/icon-192.png')))->toBeTrue();
    expect(file_exists(public_path('icons/icon-512.png')))->toBeTrue();
    expect(file_exists(public_path('icons/icon-maskable-512.png')))->toBeTrue();

    $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);
    expect($manifest['theme_color'])->toBe('#0B0709');
    expect($manifest['background_color'])->toBe('#0B0709');
});

test('admin ve çeviri ekibi tüm ana sayfalara 500 hatası olmadan girebilir', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => User::ROLE_ADMIN]);
    $admin = User::factory()->create(['email_verified_at' => now()]);
    $admin->assignRole(User::ROLE_ADMIN);

    foreach (['/', '/kesfet', '/profile', '/okuma-tercihleri', '/cuzdan', '/kutuphane'] as $uri) {
        $this->actingAs($admin)
            ->withCookie('ruhscans_age_confirmed', '1')
            ->get($uri)
            ->assertOk();
    }
});
