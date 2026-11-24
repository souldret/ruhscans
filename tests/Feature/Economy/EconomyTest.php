<?php

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Enums\LedgerDirection;
use App\Models\Chapter;
use App\Models\ChapterUnlock;
use App\Models\RedeemCode;
use App\Models\Series;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Economy\ChapterUnlocker;
use App\Services\Economy\CodeGenerator;
use App\Services\Economy\CodeHasher;
use App\Services\Economy\CodeRedeemer;
use App\Services\Economy\VipService;
use App\Services\Economy\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

function pricedChapter(int $price = 10): array
{
    $series = Series::factory()->create(['hidden' => false]);
    $chapter = Chapter::factory()->create([
        'series_id' => $series->id,
        'number' => 3,
        'published_at' => now()->subHour(),
        'ruh_tasi_fiyat' => $price,
        'vip_ucretsiz' => false,
    ]);

    return [$series, $chapter];
}

test('bakiye doğrudan güncellenemez ve negatif olamaz', function () {
    $user = User::factory()->create(['ruh_tasi_bakiye' => 4]);

    expect(fn () => $user->forceFill(['ruh_tasi_bakiye' => 99])->save())
        ->toThrow(RuntimeException::class);

    expect($user->fresh()->ruh_tasi_bakiye)->toBe(4);

    $wallet = app(WalletService::class);

    expect(fn () => $wallet->debit($user, 5, 'test', 'borc-fazla'))
        ->toThrow(\App\Exceptions\Economy\InsufficientBalanceException::class);

    expect($user->fresh()->ruh_tasi_bakiye)->toBe(4)
        ->and(WalletTransaction::query()->count())->toBe(0);
});

test('ledger kredi ve borcu idempotent yazar', function () {
    $user = User::factory()->create();
    $wallet = app(WalletService::class);

    $first = $wallet->credit($user, 20, 'yukleme', 'anahtar-1');
    $again = $wallet->credit($user->fresh(), 20, 'yukleme', 'anahtar-1');

    expect($first->id)->toBe($again->id)
        ->and($user->fresh()->ruh_tasi_bakiye)->toBe(20)
        ->and(WalletTransaction::query()->count())->toBe(1);

    $debit = $wallet->debit($user->fresh(), 7, 'harcama', 'anahtar-2');

    expect($debit->yon)->toBe(LedgerDirection::Borc)
        ->and($debit->bakiye_sonrasi)->toBe(13)
        ->and($user->fresh()->ruh_tasi_bakiye)->toBe(13);
});

test('yetersiz bakiyede bölüm açılmaz ve çift açılış tek borç yazar', function () {
    [$series, $chapter] = pricedChapter(8);
    $poor = User::factory()->create(['ruh_tasi_bakiye' => 3]);

    $this->actingAs($poor)
        ->from(route('reader.show', [$series, $chapter->number]))
        ->post(route('reader.unlock', [$series, $chapter->number]))
        ->assertRedirect(route('reader.show', [$series, $chapter->number]))
        ->assertSessionHasErrors('unlock');

    expect(ChapterUnlock::query()->count())->toBe(0)
        ->and($poor->fresh()->ruh_tasi_bakiye)->toBe(3);

    $buyer = User::factory()->create(['ruh_tasi_bakiye' => 20]);
    $unlocker = app(ChapterUnlocker::class);

    $unlocker->unlock($buyer, $chapter);
    $unlocker->unlock($buyer->fresh(), $chapter);

    expect(ChapterUnlock::query()->count())->toBe(1)
        ->and($buyer->fresh()->ruh_tasi_bakiye)->toBe(12)
        ->and(WalletTransaction::query()->where('sebep', 'bolum_acma')->count())->toBe(1);

    $this->actingAs($buyer)
        ->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertJsonPath('locked', false);
});

test('erken erişim bitince bölüm herkese açık olur', function () {
    $series = Series::factory()->create(['hidden' => false]);
    $chapter = Chapter::factory()->create([
        'series_id' => $series->id,
        'number' => 2,
        'published_at' => now()->subDay(),
        'ruh_tasi_fiyat' => 15,
        'erken_erisim_bitis' => now()->subMinute(),
        'vip_ucretsiz' => false,
    ]);

    $this->getJson(route('reader.pages', [$series, $chapter->number]))
        ->assertJsonPath('locked', false);
});

test('vip gün ekler ve süresi dolmuşsa yeniden başlatır', function () {
    $active = User::factory()->create(['vip_bitis' => now()->addDays(3)]);
    $until = app(VipService::class)->extend($active, 10);

    expect($until->greaterThan(now()->addDays(12)))->toBeTrue();

    $expired = User::factory()->create(['vip_bitis' => now()->subDay()]);
    $restarted = app(VipService::class)->extend($expired, 5);

    expect($restarted->greaterThan(now()->addDays(4)))->toBeTrue()
        ->and($restarted->lessThan(now()->addDays(6)))->toBeTrue();
});

test('kod üretimi düz kod saklamaz ve kullanımı bakiyeye yazar', function () {
    $generated = app(CodeGenerator::class)->generate(2, CodeKind::RuhTasi, 25, now()->addMonth(), 'Test partisi');
    $plain = $generated['codes'][0]['kod'];

    expect($plain)->toMatch('/^RUH-[2-9A-HJKMNP-Z]{4}(-[2-9A-HJKMNP-Z]{4}){3}$/')
        ->and(RedeemCode::query()->where('kod_hash', $plain)->exists())->toBeFalse()
        ->and(RedeemCode::query()->where('kod_hash', app(CodeHasher::class)->hashPlain($plain))->exists())->toBeTrue();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('wallet.redeem'), ['kod' => strtolower(str_replace('-', ' ', $plain))])
        ->assertRedirect(route('wallet.show', ['parla' => 1]));

    expect($user->fresh()->ruh_tasi_bakiye)->toBe(25);

    $this->actingAs($user)
        ->get(route('wallet.show', ['parla' => 1]))
        ->assertOk()
        ->assertSee('25')
        ->assertSee('stone-burst')
        ->assertSee('Sonteklif');

    $this->actingAs($user)
        ->post(route('wallet.redeem'), ['kod' => $plain])
        ->assertSessionHasErrors('kod');

    expect($user->fresh()->ruh_tasi_bakiye)->toBe(25);
});

test('süresi dolmuş ve iptal kodlar aynı hatayı verir', function () {
    $user = User::factory()->create();
    $generator = app(CodeGenerator::class);

    $expired = $generator->generate(1, CodeKind::RuhTasi, 5, now()->subMinute(), 'Eski');
    $cancelled = $generator->generate(1, CodeKind::Vip, 7, now()->addDay(), 'İptal');

    RedeemCode::query()->where('batch_id', $cancelled['batch']->id)->update([
        'durum' => CodeStatus::Iptal->value,
    ]);

    foreach ([$expired['codes'][0]['kod'], $cancelled['codes'][0]['kod'], 'RUH-AAAA-BBBB-CCCC-DDDD'] as $plain) {
        $this->actingAs($user)
            ->from(route('wallet.show'))
            ->post(route('wallet.redeem'), ['kod' => $plain])
            ->assertSessionHasErrors(['kod' => 'Kod kullanılamadı.']);
    }

    expect($user->fresh()->ruh_tasi_bakiye)->toBe(0)
        ->and($user->fresh()->vip_bitis)->toBeNull();
});

test('vip kodu aktif üyeliği uzatır', function () {
    $user = User::factory()->create(['vip_bitis' => now()->addDays(2)]);
    $generated = app(CodeGenerator::class)->generate(1, CodeKind::Vip, 30, null, 'VIP');

    app(CodeRedeemer::class)->redeem($user, $generated['codes'][0]['kod'], '127.0.0.1');

    expect($user->fresh()->vip_bitis->greaterThan(now()->addDays(31)))->toBeTrue();
});

test('aynı kodun eşzamanlı iki kullanımı yalnız birini geçirir', function () {
 if (DB::connection()->getDriverName() !== 'sqlite') {
 expect(true)->toBeTrue();

 return;
 }

 $source = database_path('race-'.uniqid('', true).'.sqlite');
 if (file_exists($source)) {
 unlink($source);
 }
 touch($source);

 config([
 'database.connections.sqlite_race' => array_merge(config('database.connections.sqlite'), [
 'database' => $source,
 'busy_timeout' => 5000,
 ]),
 ]);
 DB::purge('sqlite_race');

 [$plain, $first, $second] = DB::usingConnection('sqlite_race', function () {
 $this->artisan('migrate', ['--force' => true]);
 $generated = app(CodeGenerator::class)->generate(1, CodeKind::RuhTasi, 40, null, 'Yarış');

 return [
 $generated['codes'][0]['kod'],
 User::factory()->create(),
 User::factory()->create(),
 ];
 });

 $php = PHP_BINARY;
 $script = tempnam(sys_get_temp_dir(), 'ruh-race-').'.php';

 file_put_contents($script, <<<'PHP'
<?php
$root = $argv[1];
$database = $argv[2];
$userId = (int) $argv[3];
$plain = $argv[4];
$hold = (int) $argv[5];

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config([
 'database.connections.sqlite.database' => $database,
 'database.connections.sqlite.busy_timeout' => 5000,
 'cache.default' => 'array',
 'economy.hmac_key' => 'test-hmac-key',
]);
Illuminate\Support\Facades\DB::purge('sqlite');

if ($hold > 0) {
 usleep($hold);
}

try {
 $user = App\Models\User::query()->findOrFail($userId);
 app(App\Services\Economy\CodeRedeemer::class)->redeem($user, $plain, '10.0.0.'.$userId);
 fwrite(STDOUT, "win\n");
} catch (Throwable $exception) {
 fwrite(STDOUT, "lose\n");
}
PHP);

 $launch = static function (int $userId, int $hold) use ($php, $script, $source, $plain): mixed {
 $command = escapeshellarg($php).' '.escapeshellarg($script).' '
 .escapeshellarg(base_path()).' '.escapeshellarg($source).' '
 .$userId.' '.escapeshellarg($plain).' '.$hold;

 return popen($command, 'r');
 };

 $one = $launch($first->id, 0);
 usleep(150000);
 $two = $launch($second->id, 0);
 $results = trim((string) stream_get_contents($one)).' '.trim((string) stream_get_contents($two));
 pclose($one);
 pclose($two);
 @unlink($script);

 [$used, $credits, $balance] = DB::usingConnection('sqlite_race', function () use ($first, $second) {
 return [
 (int) DB::table('redeem_codes')->where('durum', CodeStatus::Kullanildi->value)->count(),
 (int) DB::table('wallet_transactions')->where('sebep', 'kod')->count(),
 (int) DB::table('users')->whereIn('id', [$first->id, $second->id])->sum('ruh_tasi_bakiye'),
 ];
 });

 DB::disconnect('sqlite_race');
 @unlink($source);

 expect(substr_count($results, 'win'))->toBe(1)
 ->and(substr_count($results, 'lose'))->toBe(1)
 ->and($balance)->toBe(40)
 ->and($used)->toBe(1)
 ->and($credits)->toBe(1);
});

test('on yanlış denemeden sonra kilitlenir ve dakika sınırı çalışır', function () {
    $user = User::factory()->create();
    RateLimiter::clear('kod-deneme:u:'.$user->id);
    RateLimiter::clear('kod-deneme:ip:127.0.0.1');
    RateLimiter::clear('kod-hata:u:'.$user->id);
    RateLimiter::clear('kod-hata:ip:127.0.0.1');
    RateLimiter::clear('kod-kilit:u:'.$user->id);
    RateLimiter::clear('kod-kilit:ip:127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($user)
            ->post(route('wallet.redeem'), ['kod' => 'RUH-ZZZZ-YYYY-XXXX-WWWW'])
            ->assertSessionHasErrors(['kod' => 'Kod kullanılamadı.']);
    }

    $this->actingAs($user)
        ->post(route('wallet.redeem'), ['kod' => 'RUH-ZZZZ-YYYY-XXXX-VVVV'])
        ->assertSessionHasErrors(['kod' => 'Çok hızlı deniyorsun. Bir dakika bekle.']);

    RateLimiter::clear('kod-deneme:u:'.$user->id);
    RateLimiter::clear('kod-deneme:ip:127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        $this->actingAs($user)
            ->post(route('wallet.redeem'), ['kod' => 'BOZUK-'.$attempt]);
    }

    $this->actingAs($user)
        ->post(route('wallet.redeem'), ['kod' => 'RUH-2345-6789-ABCD-EFGH'])
        ->assertSessionHasErrors(['kod' => 'Çok fazla hatalı deneme. Bir saat sonra tekrar dene.']);
});

test('sonteklif bağlantısı admin ayarından okunur', function () {
    SiteSetting::put('sonteklif_url', 'https://ornek.sonteklif.test/ruh');
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('wallet.show'))
        ->assertOk()
        ->assertSee('https://ornek.sonteklif.test/ruh', false)
        ->assertDontSee('badge-vip', false);

    $user->forceFill(['vip_bitis' => now()->addDay()])->save();

    $this->actingAs($user->fresh())
        ->get(route('wallet.show'))
        ->assertSee('VIP', false);
});

test('kilit ekranı kod ve açma akışına bağlıdır', function () {
    [$series, $chapter] = pricedChapter(6);

    $this->get(route('reader.show', [$series, $chapter->number]))
        ->assertOk()
        ->assertSee('Kod kullan')
 ->assertSee('VIP seçeneği')
 ->assertDontSee('disabled');

    $user = User::factory()->create(['ruh_tasi_bakiye' => 6]);

    $this->actingAs($user)
        ->get(route('reader.show', [$series, $chapter->number]))
        ->assertSee('ruh taşıyla aç')
        ->assertSee(route('wallet.show'), false);

    $this->actingAs($user)
        ->post(route('reader.unlock', [$series, $chapter->number]))
        ->assertRedirect(route('reader.show', [$series, $chapter->number]));

    expect($user->fresh()->ruh_tasi_bakiye)->toBe(0);
});
