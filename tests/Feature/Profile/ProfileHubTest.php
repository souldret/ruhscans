<?php

use App\Enums\LedgerDirection;
use App\Livewire\Profile\PrivacySettings;
use App\Livewire\Profile\ReadingHistoryManager;
use App\Livewire\Profile\SessionsManager;
use App\Livewire\Profile\SummaryCard;
use App\Livewire\Profile\WalletSummary;
use App\Models\Chapter;
use App\Models\ChapterUnlock;
use App\Models\LibraryEntry;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserSession;
use App\Models\WalletTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Activitylog\Models\Activity;

test('misafir profil sayfasına ve veri dışa aktarma uç noktasına erişemez', function () {
    $this->withCookie('ruhscans_age_confirmed', '1')
        ->get('/profile')
        ->assertRedirect('/login');

    $this->withCookie('ruhscans_age_confirmed', '1')
        ->get('/profile/veri-indir')
        ->assertRedirect('/login');
});

test('profil özet kartı avatarı WebP olarak özel yola yükler, kırpar ve kaldırır', function () {
    Storage::fake('local');
    config(['content.image_disk' => 'local']);

    $user = User::factory()->create([
        'vip_bitis' => now()->addDays(12),
    ]);

    $this->actingAs($user);

    $file = UploadedFile::fake()->image('avatar.png', 500, 400);

    Livewire::test(SummaryCard::class)
        ->set('avatar', $file)
        ->set('cropX', 20)
        ->set('cropY', 10)
        ->set('cropSize', 300)
        ->call('saveAvatar')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->avatar_path)->toStartWith("avatars/{$user->id}/");
    expect($user->avatar_path)->toEndWith('.webp');
    Storage::disk('local')->assertExists($user->avatar_path);

    $oldPath = $user->avatar_path;

    Livewire::test(SummaryCard::class)
        ->call('removeAvatar')
        ->assertHasNoErrors();

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('local')->assertMissing($oldPath);
});

test('avatar yükleme geçersiz dosyaları reddeder ve başka kullanıcının avatarına IDOR erişimini engeller', function () {
    Storage::fake('local');
    config(['content.image_disk' => 'local']);

    $owner = User::factory()->create();
    $other = User::factory()->create();

    Storage::disk('local')->put("avatars/{$owner->id}/test.webp", 'fake-webp');
    $owner->forceFill(['avatar_path' => "avatars/{$owner->id}/test.webp"])->save();

    $signedUrl = URL::temporarySignedRoute('profile.avatar', now()->addMinutes(10), ['user' => $owner->id]);

    $this->actingAs($other)
        ->withCookie('ruhscans_age_confirmed', '1')
        ->get($signedUrl)
        ->assertForbidden();

    $this->actingAs($owner)
        ->withCookie('ruhscans_age_confirmed', '1')
        ->get($signedUrl)
        ->assertOk();
});

test('e-posta değiştirme başka kullanıcıya ait adreste enumeration sızdırmaz', function () {
    $existing = User::factory()->create(['email' => 'dolu@example.com']);
    $user = User::factory()->create(['email' => 'ben@example.com', 'email_verified_at' => now()]);

    $this->actingAs($user);

    Volt::test('profile.update-profile-information-form')
        ->set('name', 'Yeni Ad')
        ->set('email', $existing->email)
        ->call('updateProfileInformation')
        ->assertHasNoErrors()
        ->assertSee('Değişiklikler işlendi');

    $user->refresh();
    expect($user->name)->toBe('Yeni Ad');
    expect($user->email)->toBe('ben@example.com');
    expect($user->email_verified_at)->not->toBeNull();
});

test('şifre değiştirme güçlü kural uygular, diğer oturumları kapatır ve şifreyi loglamaz', function () {
    $user = User::factory()->create(['password' => Hash::make('OldPassword!123')]);

    UserSession::create([
        'id' => 'other-device-session',
        'user_id' => $user->id,
        'ip_address' => '198.51.100.20',
        'user_agent' => 'Mozilla/5.0 (iPhone)',
        'last_activity' => now(),
    ]);

    $this->actingAs($user);

    Volt::test('profile.update-password-form')
        ->set('current_password', 'OldPassword!123')
        ->set('password', 'kisa')
        ->set('password_confirmation', 'kisa')
        ->call('updatePassword')
        ->assertHasErrors('password');

    $newPass = 'SuperGuclu!Sifre2026';

    Volt::test('profile.update-password-form')
        ->set('current_password', 'OldPassword!123')
        ->set('password', $newPass)
        ->set('password_confirmation', $newPass)
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check($newPass, $user->fresh()->password))->toBeTrue();
    expect(UserSession::query()->where('id', 'other-device-session')->exists())->toBeFalse();

    $lastLog = Activity::query()->where('log_name', 'profile')->latest('id')->first();
    expect($lastLog)->not->toBeNull();
    expect(json_encode($lastLog->properties))->not->toContain($newPass);
});

test('hesap silme kişisel verileri siler ve ledger kayıtlarını anonimleştirir', function () {
    $user = User::factory()->create(['password' => Hash::make('DeletePass!123')]);

    $tx = WalletTransaction::query()->create([
        'user_id' => $user->id,
        'yon' => LedgerDirection::Kredi,
        'miktar' => 50,
        'bakiye_sonrasi' => 50,
        'sebep' => 'kod_kullanimi',
        'idempotency_key' => 'test-delete-anon-1',
    ]);

    $this->actingAs($user);

    Volt::test('profile.delete-user-form')
        ->set('password', 'DeletePass!123')
        ->call('deleteUser')
        ->assertHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    $tx->refresh();
    expect($tx->user_id)->toBeNull();
    expect($tx->miktar)->toBe(50);
});

test('gizlilik tercihleri anında kaydolur ve panik adresi açık yönlendirmeyi engeller', function () {
    $user = User::factory()->create([
        'kapak_blur' => true,
        'gizli_mod' => false,
        'notr_sekme_baslik' => false,
    ]);
    $tag = Tag::factory()->create(['name' => 'Karanlık', 'slug' => 'karanlik']);

    $this->actingAs($user);

    Livewire::test(PrivacySettings::class)
        ->set('kapak_blur', false)
        ->set('gizli_mod', true)
        ->set('notr_sekme_baslik', true)
        ->call('toggleTag', $tag->slug)
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->kapak_blur)->toBeFalse();
    expect($user->gizli_mod)->toBeTrue();
    expect($user->notr_sekme_baslik)->toBeTrue();
    expect($user->etiket_karaliste)->toContain('karanlik');

    // Geçersiz / tehlikeli panik URL'leri reddedilir.
    foreach (['javascript:alert(1)', 'http://localhost/test', 'http://127.0.0.1/admin', 'ftp://example.com'] as $badUrl) {
        Livewire::test(PrivacySettings::class)
            ->set('panik_url', $badUrl)
            ->call('savePanicUrl')
            ->assertHasErrors('panik_url');
    }

    // Geçerli https URL kabul edilir.
    Livewire::test(PrivacySettings::class)
        ->set('panik_url', 'https://tr.wikipedia.org/wiki/Anasayfa')
        ->call('savePanicUrl')
        ->assertHasNoErrors();

    expect($user->fresh()->panik_url)->toBe('https://tr.wikipedia.org/wiki/Anasayfa');
});

test('oturum yönetimi kendi oturumlarını kapatır ama başka kullanıcının oturumuna dokunamaz', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    UserSession::create([
        'id' => 'own-other-session',
        'user_id' => $user->id,
        'ip_address' => '198.51.100.1',
        'user_agent' => 'Mozilla/5.0 (Android)',
        'last_activity' => now(),
    ]);

    UserSession::create([
        'id' => 'victim-session',
        'user_id' => $other->id,
        'ip_address' => '198.51.100.2',
        'user_agent' => 'Mozilla/5.0 (Windows)',
        'last_activity' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(SessionsManager::class)
        ->call('revokeSession', 'victim-session')
        ->call('revokeSession', 'own-other-session');

    expect(UserSession::query()->whereKey('victim-session')->exists())->toBeTrue();
    expect(UserSession::query()->whereKey('own-other-session')->exists())->toBeFalse();
});

test('okuma geçmişi tek tek ve toplu temizlenir, başka kullanıcının kaydı silinemez', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $series = Series::factory()->create();
    $chapter = Chapter::factory()->for($series)->create(['number' => 1, 'published_at' => now()]);

    $ownEntry = LibraryEntry::query()->create([
        'user_id' => $user->id,
        'series_id' => $series->id,
        'last_chapter_id' => $chapter->id,
        'last_page_order' => 4,
    ]);

    $otherEntry = LibraryEntry::query()->create([
        'user_id' => $other->id,
        'series_id' => $series->id,
        'last_chapter_id' => $chapter->id,
        'last_page_order' => 9,
    ]);

    $this->actingAs($user);

    Livewire::test(ReadingHistoryManager::class)
        ->call('removeEntry', $otherEntry->id)
        ->call('removeEntry', $ownEntry->id);

    expect($otherEntry->fresh()->last_chapter_id)->toBe($chapter->id);
    expect($ownEntry->fresh()->last_chapter_id)->toBeNull();
});

test('cüzdan özeti ve JSON veri dışa aktarma yalnızca kullanıcının kendi verisini döner', function () {
    $user = User::factory()->create(['email' => 'sahip@example.com']);
    $other = User::factory()->create(['email' => 'baskasi@example.com']);
    $series = Series::factory()->create(['title' => 'Özel Seri']);
    $chapter = Chapter::factory()->for($series)->create(['number' => 5, 'published_at' => now()]);

    WalletTransaction::query()->create([
        'user_id' => $user->id,
        'yon' => LedgerDirection::Kredi,
        'miktar' => 25,
        'bakiye_sonrasi' => 25,
        'sebep' => 'benim_islemim',
        'idempotency_key' => 'own-tx-1',
    ]);

    WalletTransaction::query()->create([
        'user_id' => $other->id,
        'yon' => LedgerDirection::Kredi,
        'miktar' => 999,
        'bakiye_sonrasi' => 999,
        'sebep' => 'baskasinin_islemi',
        'idempotency_key' => 'other-tx-1',
    ]);

    ChapterUnlock::query()->create([
        'user_id' => $user->id,
        'chapter_id' => $chapter->id,
        'ruh_tasi_odenen' => 2,
    ]);

    $this->actingAs($user);

    Livewire::test(WalletSummary::class)
        ->assertSee('benim_islemim')
        ->assertDontSee('baskasinin_islemi')
        ->assertSee('Özel Seri');

    $response = $this->withCookie('ruhscans_age_confirmed', '1')
        ->get('/profile/veri-indir')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json; charset=UTF-8');

    $json = $response->json();
    expect($json['hesap']['eposta'])->toBe('sahip@example.com');
    expect($json['hesap'])->not->toHaveKey('password');
    expect($json['cuzdan_hareketleri'])->toHaveCount(1);
    expect($json['cuzdan_hareketleri'][0]['sebep'])->toBe('benim_islemim');
    expect($json['acilan_bolumler'][0]['seri'])->toBe('Özel Seri');
});
