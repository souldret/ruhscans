<?php

use App\Models\Chapter;
use App\Models\Series;
use App\Models\TranslatorGroup;
use App\Models\User;
use App\Policies\ChapterPolicy;
use App\Policies\SeriesPolicy;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('çeviri ekibi yalnızca kendi grubunun serisini yönetir', function () {
    $own = TranslatorGroup::factory()->create();
    $other = TranslatorGroup::factory()->create();
    $user = User::factory()->create(['translator_group_id' => $own->id]);
    $user->assignRole(User::ROLE_CEVIRI);

    $ownSeries = Series::factory()->create(['translator_group_id' => $own->id]);
    $otherSeries = Series::factory()->create(['translator_group_id' => $other->id]);

    $policy = app(SeriesPolicy::class);

    expect($policy->update($user, $ownSeries))->toBeTrue()
        ->and($policy->update($user, $otherSeries))->toBeFalse()
        ->and($policy->delete($user, $otherSeries))->toBeFalse()
        ->and($policy->view($user, $otherSeries))->toBeFalse();
});

test('grupsuz çeviri ekibi seri oluşturamaz', function () {
    $user = User::factory()->create(['translator_group_id' => null]);
    $user->assignRole(User::ROLE_CEVIRI);

    expect(app(SeriesPolicy::class)->create($user))->toBeFalse();
});

test('üye seri yönetemez ve yönetici her seriyi yönetir', function () {
    $series = Series::factory()->create();
    $member = User::factory()->create();
    $member->assignRole(User::ROLE_UYE);
    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_ADMIN);

    $policy = app(SeriesPolicy::class);

    expect($policy->viewAny($member))->toBeFalse()
        ->and($policy->update($member, $series))->toBeFalse()
        ->and($policy->update($admin, $series))->toBeTrue();
});

test('bölüm yetkisi serinin çeviri ekibine bağlıdır', function () {
    $group = TranslatorGroup::factory()->create();
    $user = User::factory()->create(['translator_group_id' => $group->id]);
    $user->assignRole(User::ROLE_CEVIRI);

    $own = Chapter::factory()->for(Series::factory()->create(['translator_group_id' => $group->id]))->create();
    $foreign = Chapter::factory()->create();

    $policy = app(ChapterPolicy::class);

    expect($policy->update($user, $own))->toBeTrue()
        ->and($policy->delete($user, $foreign))->toBeFalse();
});
