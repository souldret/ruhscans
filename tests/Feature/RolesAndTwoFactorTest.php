<?php

use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RoleSeeder;
use Filament\Panel;
use PragmaRX\Google2FA\Google2FA;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('üye filament paneline giremez', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_UYE);

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('çeviri ekibi panele girebilir', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_CEVIRI);

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeTrue();
});

test('iki adımı olmayan yönetici panele giremez ama kurulum sayfasına girer', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();

    $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(route('admin.two-factor'));

    $this->actingAs($user)
        ->get(route('admin.two-factor'))
        ->assertOk()
        ->assertSee('Gizli anahtar');
});

test('kurulum onayından sonra yönetici panele girer', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);

    $secret = app(Google2FA::class)->generateSecretKey();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->actingAs($user)
            ->withSession(['admin_2fa_secret' => $secret])
            ->post(route('admin.two-factor.store'), ['code' => $code])
            ->assertRedirect(route('admin.two-factor.recovery'))
            ->assertSessionHas('admin_2fa_passed', true);

        $user->refresh();

        expect($user->hasConfirmedTwoFactor())->toBeTrue()
            ->and($user->canAccessPanel(filament()->getPanel('admin')))->toBeTrue();

        $this->actingAs($user)
            ->withSession(['admin_2fa_passed' => true])
            ->get('/admin')
            ->assertOk();
});

test('kurtarma kodu bir kez panele giriş sağlar', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);
    $plain = 'AB2CD-EF3GH';
    $user->forceFill([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => [\App\Services\TwoFactor\RecoveryCode::hash([$plain])[0]],
    ])->save();

    $this->actingAs($user)
        ->post(route('admin.two-factor.verify'), ['code' => $plain])
        ->assertRedirect('/admin');

    expect($user->fresh()->two_factor_recovery_codes)->toBe([]);

    $this->actingAs($user->fresh())
        ->post(route('admin.two-factor.verify'), ['code' => $plain])
        ->assertSessionHasErrors('code');
});

test('kayıp cihaz komutu iki adımı sıfırlar', function () {
    $user = User::factory()->create(['email' => 'admin@ruhscans.test']);
    $user->assignRole(User::ROLE_ADMIN);
    $user->forceFill([
        'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => ['hash'],
    ])->save();

    $this->artisan('user:reset-2fa', ['email' => 'admin@ruhscans.test'])
        ->assertSuccessful();

    expect($user->fresh()->hasConfirmedTwoFactor())->toBeFalse()
        ->and($user->fresh()->two_factor_recovery_codes)->toBeNull();
});

test('yönetici geçerli totp koduyla iki adımı onaylar', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);

    $secret = app(Google2FA::class)->generateSecretKey();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->actingAs($user)
        ->withSession(['admin_2fa_secret' => $secret])
        ->post(route('admin.two-factor.store'), ['code' => $code])
        ->assertRedirect(route('admin.two-factor.recovery'));

    $user->refresh();

    expect($user->hasConfirmedTwoFactor())->toBeTrue()
        ->and($user->canAccessPanel(filament()->getPanel('admin')))->toBeTrue()
        ->and(app(TwoFactorService::class)->verify($user->two_factor_secret, $code))->toBeTrue();
});

test('panel kimliği dışında erişim verilmez', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_CEVIRI);
    $panel = Panel::make()->id('baska');

    expect($user->canAccessPanel($panel))->toBeFalse();
});
