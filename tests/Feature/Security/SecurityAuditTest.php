<?php

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Exceptions\Economy\CodeRedeemException;
use App\Models\CodeBatch;
use App\Models\RedeemCode;
use App\Models\Series;
use App\Models\User;
use App\Services\Economy\CodeExportStore;
use App\Services\Economy\CodeHasher;
use App\Services\Economy\CodeRedeemer;
use App\Services\Economy\WalletService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Spatie\Activitylog\Models\Activity;

test('güvenilmeyen kaynaktan gelen CF-Connecting-IP başlığı yok sayılır', function () {
    $response = $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.77'])
        ->withHeader('CF-Connecting-IP', '203.0.113.1')
        ->get('/kullanim-sartlari');

    $response->assertOk();
    expect(request()->ip())->toBe('198.51.100.77');
});

test('resmi Cloudflare aralığından gelen CF-Connecting-IP gerçek istemci IP olarak kabul edilir', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '173.245.48.10'])
        ->withHeader('CF-Connecting-IP', '203.0.113.42')
        ->get('/kullanim-sartlari')
        ->assertOk();

    expect(request()->ip())->toBe('203.0.113.42');
});

test('yaş kapısı açık yönlendirme ve önek atlatma denemelerini ana sayfaya düşürür', function (string $badTarget) {
    $this->post(route('age-gate.confirm'), ['intended' => $badTarget])
        ->assertRedirect(route('home'));
})->with([
    'dış alan adı' => ['https://evil.example/phish'],
    'önek atlatma' => ['http://localhost.evil.example/phish'],
    'protokol göreli' => ['//evil.example/phish'],
    'ters eğik çizgi' => ['https://localhost\\@evil.example'],
    'javascript şeması' => ['javascript:alert(1)'],
]);

test('JSON-LD şemasında script kapatma etiketi XSS için kaçışlanır', function () {
    $series = Series::factory()->create([
        'title' => 'Test </script><script>alert(1)</script>',
        'hidden' => false,
    ]);

    $response = $this->withCookie('ruhscans_age_confirmed', '1')
        ->get(route('series.show', $series))
        ->assertOk();

    $response->assertDontSee('</script><script>alert(1)</script>', false);
    $response->assertSee('\u003C/script\u003E', false);
});

test('kod kullanımında IP değiştirerek kullanıcı kilidi ve hesap değiştirerek IP kilidi atlatılamaz', function () {
    config([
        'economy.hmac_key' => 'test-secret-hmac-key-2026',
        'economy.redeem_failures_before_lock' => 3,
        'economy.redeem_attempts_per_minute' => 20,
    ]);

    $redeemer = app(CodeRedeemer::class);
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Kullanıcı 1 farklı IP'lerden 3 kez hatalı deneme yapar -> kullanıcı bazlı kilitlenir.
    foreach (['203.0.113.1', '203.0.113.2', '203.0.113.3'] as $ip) {
        try {
            $redeemer->redeem($user1, 'RUH-2222-2222-2222-2222', $ip);
        } catch (CodeRedeemException) {
        }
    }

    expect(fn () => $redeemer->redeem($user1, 'RUH-3333-3333-3333-3333', '203.0.113.99'))
        ->toThrow(CodeRedeemException::class, 'Çok fazla hatalı deneme');

    // Aynı IP'den farklı hesaplarla 3 hatalı deneme -> IP bazlı kilitlenir.
    $sharedIp = '198.51.100.50';
    foreach (range(1, 3) as $i) {
        $u = User::factory()->create();
        try {
            $redeemer->redeem($u, 'RUH-2222-2222-2222-2222', $sharedIp);
        } catch (CodeRedeemException) {
        }
    }

    expect(fn () => $redeemer->redeem($user2, 'RUH-3333-3333-3333-3333', $sharedIp))
        ->toThrow(CodeRedeemException::class, 'Çok fazla hatalı deneme');
});

test('tek seferlik kod CSV indirmesi atomiktir ve ikinci denemede reddedilir', function () {
    Storage::fake('local');

    $batch = CodeBatch::query()->create([
        'ad' => 'Test Parti',
        'tur' => CodeKind::RuhTasi,
        'deger' => 10,
        'adet' => 1,
    ]);

    $store = app(CodeExportStore::class);
    $export = $store->stash($batch, "kod\nRUH-2222-2222-2222-2222\n");

    $first = $store->pullAndConsume($export->token);
    $second = $store->pullAndConsume($export->token);

    expect($first)->not->toBeNull()
        ->and($first['csv'])->toContain('RUH-2222-2222-2222-2222')
        ->and($second)->toBeNull();

    Storage::disk('local')->assertMissing($export->path);
});

test('Horizon kapısı yalnızca engelli olmayan admin rolüne açıktır', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => User::ROLE_ADMIN]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => User::ROLE_CEVIRI]);

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $blockedAdmin = User::factory()->create(['blocked_at' => now()]);
    $blockedAdmin->assignRole(User::ROLE_ADMIN);

    $translator = User::factory()->create();
    $translator->assignRole(User::ROLE_CEVIRI);

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue()
        ->and(Gate::forUser($blockedAdmin)->allows('viewHorizon'))->toBeFalse()
        ->and(Gate::forUser($translator)->allows('viewHorizon'))->toBeFalse()
        ->and(Gate::forUser(null)->allows('viewHorizon'))->toBeFalse();
});

test('şifre sıfırlama formu kayıtlı olmayan e-postada kullanıcı numaralandırma sızdırmaz', function () {
    config(['turnstile.enabled' => false]);

    Volt::test('pages.auth.forgot-password')
        ->set('email', 'olmayan-hesap-999@example.com')
        ->call('sendPasswordResetLink')
        ->assertHasNoErrors();
});

test('economy:verify komutu tutarlı defterde başarılı olur ve tutarsızlıkta uyarı yazar', function () {
    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 40, 'kod', 'verify-test-1');

    expect(Artisan::call('economy:verify'))->toBe(0);

    // Doğrudan DB müdahalesiyle tutarsızlık oluştur.
    DB::table('users')->where('id', $user->id)->update(['ruh_tasi_bakiye' => 999]);

    expect(Artisan::call('economy:verify'))->toBe(1);

    $auditLog = Activity::query()->where('log_name', 'economy-audit')->latest('id')->first();
    expect($auditLog)->not->toBeNull()
        ->and($auditLog->properties['mismatch_count'])->toBe(1);
});

test('aynı kod iki ayrı kullanıcı tarafından art arda/eşzamanlı kullanıldığında yalnız biri alır ve ledger tutarlı kalır', function () {
    config(['economy.hmac_key' => 'test-secret-hmac-key-2026']);

    $plain = 'RUH-ABCD-EFGH-JKMN-PQRS';
    $hasher = app(CodeHasher::class);
    $batch = CodeBatch::query()->create([
        'ad' => 'Paralel Parti',
        'tur' => CodeKind::RuhTasi,
        'deger' => 100,
        'adet' => 1,
    ]);

    RedeemCode::query()->create([
        'batch_id' => $batch->id,
        'tur' => CodeKind::RuhTasi,
        'deger' => 100,
        'durum' => CodeStatus::Aktif,
        'kod_hash' => $hasher->hashPlain($plain),
        'son_dort' => $hasher->lastFour($plain),
    ]);

    $u1 = User::factory()->create();
    $u2 = User::factory()->create();
    $redeemer = app(CodeRedeemer::class);

    $success = 0;
    $failed = 0;

    foreach ([$u1, $u2] as $u) {
        try {
            $redeemer->redeem($u, $plain, '203.0.113.10');
            $success++;
        } catch (CodeRedeemException) {
            $failed++;
        }
    }

    expect($success)->toBe(1)
        ->and($failed)->toBe(1)
        ->and($u1->fresh()->ruh_tasi_bakiye + $u2->fresh()->ruh_tasi_bakiye)->toBe(100)
        ->and(Artisan::call('economy:verify'))->toBe(0);
});
