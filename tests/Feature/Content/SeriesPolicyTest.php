<?php

use App\Models\Chapter;
use App\Models\Series;
use App\Models\User;
use App\Policies\ChapterPolicy;
use App\Policies\SeriesPolicy;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

test('çeviri ekibi tüm serileri yönetir', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_CEVIRI);

    $series = Series::factory()->create();

    $policy = app(SeriesPolicy::class);

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->view($user, $series))->toBeTrue()
        ->and($policy->update($user, $series))->toBeTrue()
        ->and($policy->delete($user, $series))->toBeTrue();
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

test('çeviri ekibi tüm bölümleri yönetir', function () {
    $user = User::factory()->create();
    $user->assignRole(User::ROLE_CEVIRI);

    $chapter = Chapter::factory()->create();

    $policy = app(ChapterPolicy::class);

    expect($policy->viewAny($user))->toBeTrue()
        ->and($policy->create($user))->toBeTrue()
        ->and($policy->view($user, $chapter))->toBeTrue()
        ->and($policy->update($user, $chapter))->toBeTrue()
        ->and($policy->delete($user, $chapter))->toBeTrue();
});
