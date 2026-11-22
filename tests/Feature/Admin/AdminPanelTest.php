<?php

use App\Enums\CodeKind;
use App\Enums\LedgerDirection;
use App\Filament\Pages\EconomySettings;
use App\Filament\Pages\GenerateCodes;
use App\Filament\Pages\SiteSettings;
use App\Filament\Pages\SystemStatus;
use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\CodeBatchResource;
use App\Filament\Resources\RedeemCodeResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SeriesResource;
use App\Filament\Resources\TagResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\WalletTransactionResource;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Admin\MemberAdmin;
use App\Services\Economy\CodeExportStore;
use App\Services\Economy\CodeGenerator;
use App\Services\Ops\SiteOperations;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function panelUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user->fresh();
}

test('admin ekonomi kullanıcı ve sistem menülerini görür', function () {
    $admin = panelUser(User::ROLE_ADMIN);

    $this->actingAs($admin);

    expect(SeriesResource::canViewAny())->toBeTrue()
        ->and(TagResource::canViewAny())->toBeTrue()
        ->and(CodeBatchResource::canViewAny())->toBeTrue()
        ->and(RedeemCodeResource::canViewAny())->toBeTrue()
        ->and(WalletTransactionResource::canViewAny())->toBeTrue()
        ->and(UserResource::canViewAny())->toBeTrue()
        ->and(RoleResource::canViewAny())->toBeTrue()
        ->and(ActivityLogResource::canViewAny())->toBeTrue()
        ->and(GenerateCodes::canAccess())->toBeTrue()
        ->and(SiteSettings::canAccess())->toBeTrue()
        ->and(SystemStatus::canAccess())->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', User::factory()->create()))->toBeTrue();
});

test('çeviri ekibi ekonomi kullanıcı ve sistem menülerini görmez', function () {
    $editor = panelUser(User::ROLE_CEVIRI);

    $this->actingAs($editor);

    expect(SeriesResource::canViewAny())->toBeTrue()
        ->and(TagResource::canViewAny())->toBeTrue()
        ->and(CodeBatchResource::canViewAny())->toBeFalse()
        ->and(RedeemCodeResource::canViewAny())->toBeFalse()
        ->and(WalletTransactionResource::canViewAny())->toBeFalse()
        ->and(UserResource::canViewAny())->toBeFalse()
        ->and(RoleResource::canViewAny())->toBeFalse()
        ->and(ActivityLogResource::canViewAny())->toBeFalse()
        ->and(GenerateCodes::canAccess())->toBeFalse()
        ->and(SiteSettings::canAccess())->toBeFalse()
        ->and(SystemStatus::canAccess())->toBeFalse()
        ->and(EconomySettings::canAccess())->toBeFalse();
});

test('kod csv yalnızca bir kez iner', function () {
    $admin = panelUser(User::ROLE_ADMIN);
    $generated = app(CodeGenerator::class)->generate(1, CodeKind::RuhTasi, 15, null, 'Test');
    $export = app(CodeExportStore::class)->stash($generated['batch'], app(CodeGenerator::class)->csv($generated['codes']));

    $this->actingAs($admin)
        ->get(route('admin.codes.csv', ['token' => $export->token]))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertSee($generated['codes'][0]['kod']);

    $this->actingAs($admin)
        ->get(route('admin.codes.csv', ['token' => $export->token]))
        ->assertNotFound();
});

test('manuel ruh taşı ledger ve aktivite günlüğüne yazılır', function () {
    $admin = panelUser(User::ROLE_ADMIN);
    $member = User::factory()->create(['ruh_tasi_bakiye' => 4]);

    app(MemberAdmin::class)->adjustStones($member, $admin, 6, true, 'destek iadesi');

    expect($member->fresh()->ruh_tasi_bakiye)->toBe(10)
        ->and(WalletTransaction::query()->where('user_id', $member->id)->where('yon', LedgerDirection::Kredi)->where('miktar', 6)->exists())->toBeTrue()
        ->and(Activity::query()->where('description', 'Ruh taşı eklendi')->where('subject_id', $member->id)->exists())->toBeTrue();

    expect(fn () => app(MemberAdmin::class)->adjustStones($member, $admin, 1, false, '   '))
        ->toThrow(InvalidArgumentException::class);
});

test('site ayarları önbellekli kaydedilir', function () {
    SiteSetting::put('announcement', 'eski');
    SiteSetting::put('announcement_enabled', '0');
    SiteSetting::put('watermark_enabled', '0');
    SiteSetting::put('age_gate_text', 'Eski uyarı');

    $ops = app(SiteOperations::class);

    expect($ops->announcement())->toBeNull()
        ->and($ops->watermark())->toBeFalse()
        ->and($ops->ageGateText())->toBe('Eski uyarı');

    SiteSetting::put('announcement_enabled', '1');
    SiteSetting::put('announcement', 'Yeni duyuru');
    SiteSetting::put('watermark_enabled', '1');
    SiteSetting::put('age_gate_text', 'Yalnızca yetişkinler.');

    expect($ops->announcement())->toBe('Yeni duyuru')
        ->and($ops->watermark())->toBeTrue()
        ->and($ops->ageGateText())->toBe('Yalnızca yetişkinler.');
});
