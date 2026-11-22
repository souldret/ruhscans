<?php

use App\Services\Security\TurnstileService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();

    config()->set('turnstile.enabled', true);
    config()->set('turnstile.site_key', '1x00000000000000000000AA');
    config()->set('turnstile.secret_key', '1x0000000000000000000000000000000AA');
});

test('geçerli token doğrulanır ve cf connecting ip remoteip olarak gönderilir', function () {
    Http::fake(function (ClientRequest $request) {
        expect($request->url())->toBe('https://challenges.cloudflare.com/turnstile/v0/siteverify')
            ->and($request->data())->toMatchArray([
                'secret' => '1x0000000000000000000000000000000AA',
                'response' => 'token-1',
                'remoteip' => '203.0.113.9',
            ]);

        return Http::response(['success' => true], 200);
    });

    $request = Request::create('/', 'POST', [], [], [], [
        'HTTP_CF_CONNECTING_IP' => '203.0.113.9, 10.0.0.1',
    ]);

    expect(app(TurnstileService::class)->verify('token-1', $request))->toBeTrue();

    Http::assertSentCount(1);
});

test('siteverify başarısız döndürürse istek reddedilir', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
    ]);

    expect(app(TurnstileService::class)->verify('token-2'))->toBeFalse();
});

test('siteverify hata veya zaman aşımında istek reddedilir', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(app(TurnstileService::class)->verify('token-3'))->toBeFalse();
});

test('siteverify 500 döndürürse istek reddedilir', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 500),
    ]);

    expect(app(TurnstileService::class)->verify('token-4'))->toBeFalse();
});

test('aynı token ikinci kez kullanılamaz', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    $turnstile = app(TurnstileService::class);

    expect($turnstile->verify('token-5'))->toBeTrue()
        ->and($turnstile->verify('token-5'))->toBeFalse();

    Http::assertSentCount(1);
});

test('boş token reddedilir ve siteverify çağrılmaz', function () {
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
    ]);

    expect(app(TurnstileService::class)->verify(''))->toBeFalse();

    Http::assertSentCount(0);
});

test('turnstile kapalıyken doğrulama atlanır', function () {
    config()->set('turnstile.enabled', false);
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false], 200),
    ]);

    expect(app(TurnstileService::class)->verify('token-6'))->toBeTrue();

    Http::assertSentCount(0);
});
