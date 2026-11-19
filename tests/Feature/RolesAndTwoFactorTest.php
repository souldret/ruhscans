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

test('iki adımı olmayan yönetici panele giremez', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);

    expect($user->canAccessPanel(filament()->getPanel('admin')))->toBeFalse();
});

test('yönetici geçerli totp koduyla iki adımı onaylar', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_ADMIN);

    $secret = app(Google2FA::class)->generateSecretKey();
    $code = app(Google2FA::class)->getCurrentOtp($secret);

    $this->actingAs($user)
        ->withSession(['admin_2fa_secret' => $secret])
        ->post(route('admin.two-factor.store'), ['code' => $code])
        ->assertRedirect('/admin');

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
