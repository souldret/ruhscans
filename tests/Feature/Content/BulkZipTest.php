<?php

use App\Jobs\ProcessChapterZip;
use App\Models\Chapter;
use App\Models\Series;
use App\Services\Content\BulkChapterZipImporter;
use Illuminate\Support\Facades\Queue;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

test('toplu zip her klasörden bir bölüm kuyruğa alır', function () {
    Queue::fake();

    $manager = new ImageManager(new Driver);
    $directory = storage_path('app/bulk-zip-'.uniqid());
    mkdir($directory.'/1', 0777, true);
    mkdir($directory.'/2', 0777, true);
    file_put_contents($directory.'/1/a.png', (string) $manager->create(8, 8)->fill('#111111')->toPng());
    file_put_contents($directory.'/2/b.png', (string) $manager->create(8, 8)->fill('#222222')->toPng());

    $zipPath = $directory.'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFile($directory.'/1/a.png', '1/a.png');
    $zip->addFile($directory.'/2/b.png', '2/b.png');
    $zip->close();

    $series = Series::factory()->create();
    $ids = app(BulkChapterZipImporter::class)->queue($series, $zipPath, [
        'ruh_tasi_fiyat' => 3,
        'vip_ucretsiz' => true,
    ]);

    expect($ids)->toHaveCount(2)
 ->and(Chapter::query()->where('series_id', $series->id)->orderBy('number')->pluck('number')->map(fn ($number) => (float) $number)->all())->toBe([1.0, 2.0]);

    Queue::assertPushed(ProcessChapterZip::class, 2);

    unlink($zipPath);
    unlink($directory.'/1/a.png');
    unlink($directory.'/2/b.png');
    rmdir($directory.'/1');
    rmdir($directory.'/2');
    rmdir($directory);
});
