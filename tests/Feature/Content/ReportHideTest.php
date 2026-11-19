<?php

use App\Actions\Content\HideReportedContent;
use App\Enums\ReportStatus;
use App\Models\Chapter;
use App\Models\Report;
use App\Models\Series;
use App\Models\User;

test('şikayet tek adımda seriyi gizler', function () {
    $admin = User::factory()->create();
    $series = Series::factory()->create(['hidden' => false]);
    $report = Report::factory()->create([
        'reportable_type' => Series::class,
        'reportable_id' => $series->id,
    ]);

    app(HideReportedContent::class)($report, $admin);

    expect($series->refresh()->hidden)->toBeTrue()
        ->and($report->refresh()->status)->toBe(ReportStatus::Incelendi)
        ->and($report->reviewed_by)->toBe($admin->id);
});

test('bölüm şikayeti bölümü siler ve seriyi gizler', function () {
    $admin = User::factory()->create();
    $chapter = Chapter::factory()->create();
    $report = Report::factory()->create([
        'reportable_type' => Chapter::class,
        'reportable_id' => $chapter->id,
    ]);

    app(HideReportedContent::class)($report, $admin);

    expect($chapter->series->refresh()->hidden)->toBeTrue()
        ->and($chapter->refresh()->trashed())->toBeTrue();
});
