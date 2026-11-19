<?php

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\URL;

test('yetkisiz kullanıcı presigned sayfa adresi alamaz', function () {
    [$series, $chapter] = openChapter([
        'number' => 21,
        'ruh_tasi_fiyat' => 12,
    ]);

    $page = $chapter->pages()->first();

    $this->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertOk()
        ->assertJsonPath('locked', true)
        ->assertJsonMissingPath('pages')
        ->assertDontSee('signature=')
        ->assertDontSee('X-Amz-');

    $this->get(route('reader.media', $page))
        ->assertForbidden();

    $expired = URL::temporarySignedRoute('reader.media', now()->subMinute(), ['page' => $page->id]);

    $this->get($expired)->assertForbidden();
});

test('açık bölüm kısa ömürlü imzalı adres döner ve süre dolunca reddedilir', function () {
    [$series, $chapter] = openChapter(['number' => 22], 1);

    $response = $this->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertOk()
        ->assertJsonPath('locked', false);

    $url = $response->json('pages.0.url');

    expect($url)->toBeString()->toContain('signature=');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
    expect(isset($query['expires']))->toBeTrue()
        ->and((int) $query['expires'])->toBeLessThanOrEqual(now()->addMinutes(5)->getTimestamp())
        ->and((int) $query['expires'])->toBeGreaterThan(now()->getTimestamp());

    $this->get($url)->assertOk();

    $expired = preg_replace('/expires=\d+/', 'expires='.now()->subMinute()->getTimestamp(), $url);

    $this->get($expired)->assertForbidden();
});

test('okuma hızı aşılınca geçici bloklanır', function () {
    config([
        'security.read_rate.per_minute' => 2,
        'security.read_rate.burst_max' => 100,
    ]);

    [$series, $chapter] = openChapter(['number' => 23], 1);
    $url = route('reader.pages', [$series, $chapter->number]);

    $this->getJson($url)->assertOk();
    $this->getJson($url)->assertOk();
    $this->getJson($url)->assertStatus(429);
    $this->getJson($url)->assertStatus(429);
});

test('yabancı referer sayfa listesini çekemez', function () {
    [$series, $chapter] = openChapter(['number' => 24], 1);

    $this->withHeader('Referer', 'https://ornek-hotlink.test/oku')
        ->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertForbidden();
});

test('güvenlik başlıkları yanıta eklenir', function () {
    $this->get('/')
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

    expect($this->get('/')->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'");
});

test('cloudflare ip hız sınırına bağlanır', function () {
    config([
        'security.read_rate.per_minute' => 1,
        'security.read_rate.burst_max' => 100,
    ]);

    [$series, $chapter] = openChapter(['number' => 25], 1);
    $url = route('reader.pages', [$series, $chapter->number]);

    $this->withHeader('CF-Connecting-IP', '203.0.113.10')
        ->getJson($url)
        ->assertOk();

    $this->withHeader('CF-Connecting-IP', '203.0.113.10')
        ->getJson($url)
        ->assertStatus(429);

    $this->withHeader('CF-Connecting-IP', '203.0.113.11')
        ->getJson($url)
        ->assertOk();
});

test('şikayet honeypot kayıt oluşturmaz ve geçerli form kuyruğa düşer', function () {
    $this->post(route('legal.notice.store'), [
        'kind' => 'sikayet',
        'email' => 'bot@example.test',
        'reason' => 'Bu metin yirmi karakterden uzun bir sahte bildirimdir.',
        'website' => 'https://spam.example',
    ])->assertRedirect();

    expect(Report::query()->count())->toBe(0);

    $this->post(route('legal.notice.store'), [
        'kind' => 'dmca',
        'email' => 'hak@example.test',
        'url' => 'https://ruhscans.test/seri/ornek',
        'reason' => 'Telif ihlali olduğunu düşündüğüm bölüm için resmi bildirim.',
        'website' => '',
    ])->assertRedirect();

    expect(Report::query()->count())->toBe(1)
        ->and(Report::query()->first()->reason)->toContain('DMCA');
});

test('okuma bloğu olan kullanıcı sayfa adresi alamaz', function () {
    [$series, $chapter] = openChapter(['number' => 26], 1);
    $user = User::factory()->create();
    $user->forceFill(['read_blocked_until' => now()->addMinutes(10)])->save();

    $this->actingAs($user)
        ->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertStatus(429)
        ->assertDontSee('signature=');
});
