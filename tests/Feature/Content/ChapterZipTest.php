<?php

use App\Models\Chapter;
use App\Services\Content\ChapterZipImporter;
use App\Services\Content\NaturalSorter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

test('doğal sıralama 1 2 10 düzenini korur', function () {
    $sorted = app(NaturalSorter::class)->sort([
        'klasor/10.png',
        'klasor/2.png',
        'klasor/1.png',
    ]);

    expect(array_map('basename', $sorted))->toBe(['1.png', '2.png', '10.png']);
});

test('zip görselleri webp olarak yükler ve bozuk dosyayı raporlar', function () {
    Storage::fake('local');

    $manager = new ImageManager(new Driver);
    $directory = storage_path('app/zip-test-'.uniqid());
    mkdir($directory);

    file_put_contents($directory.'/10.png', (string) $manager->create(20, 30)->fill('#112233')->toPng());
    file_put_contents($directory.'/2.png', (string) $manager->create(20, 30)->fill('#445566')->toPng());
    file_put_contents($directory.'/1.png', (string) $manager->create(20, 30)->fill('#778899')->toPng());
    file_put_contents($directory.'/not.txt', 'bozuk');

    $zipPath = $directory.'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    foreach (['1.png', '2.png', '10.png', 'not.txt'] as $file) {
        $zip->addFile($directory.'/'.$file, $file);
    }
    $zip->close();

    $chapter = Chapter::factory()->create();
    $upload = app(ChapterZipImporter::class)->import($chapter, $zipPath);

    $chapter->refresh();

    expect($upload->status)->toBe('kismi')
        ->and($upload->failures)->toHaveCount(1)
        ->and($upload->failures[0]['file'])->toBe('not.txt')
        ->and($chapter->pages)->toHaveCount(3)
        ->and($chapter->pages->pluck('order')->all())->toBe([1, 2, 3]);

    foreach ($chapter->pages as $page) {
        Storage::disk('local')->assertExists($page->r2_path);
        expect($page->r2_path)->toEndWith('.webp')
            ->and($page->width)->toBe(20)
            ->and($page->height)->toBe(30);
    }

    expect(Storage::disk('local')->allFiles())->each->not->toContain('.png');

    unlink($zipPath);
    foreach (glob($directory.'/*') as $file) {
        unlink($file);
    }
    rmdir($directory);
});

test('uzun webtoon görseli kesilir', function () {
    Storage::fake('local');
    config(['content.max_page_height' => 40]);

    $manager = new ImageManager(new Driver);
    $directory = storage_path('app/slice-test-'.uniqid());
    mkdir($directory);
    file_put_contents($directory.'/1.png', (string) $manager->create(10, 100)->fill('#abcdef')->toPng());

    $zipPath = $directory.'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFile($directory.'/1.png', '1.png');
    $zip->close();

    $chapter = Chapter::factory()->create();
    $upload = app(ChapterZipImporter::class)->import($chapter, $zipPath);

    expect($upload->status)->toBe('tamamlandi')
        ->and($chapter->pages()->count())->toBeGreaterThan(1)
        ->and($chapter->pages()->max('height'))->toBeLessThanOrEqual(40);

    unlink($zipPath);
    unlink($directory.'/1.png');
    rmdir($directory);
});

test('geniş görsel yapılandırılmış genişliğe küçülür', function () {
    Storage::fake('local');
    config(['content.max_page_width' => 40, 'content.max_page_height' => 16000]);

    $manager = new ImageManager(new Driver);
    $directory = storage_path('app/scale-test-'.uniqid());
    mkdir($directory);
    file_put_contents($directory.'/1.png', (string) $manager->create(80, 20)->fill('#112233')->toPng());

    $zipPath = $directory.'.zip';
    $zip = new ZipArchive;
    $zip->open($zipPath, ZipArchive::CREATE);
    $zip->addFile($directory.'/1.png', '1.png');
    $zip->close();

    $chapter = Chapter::factory()->create();
    app(ChapterZipImporter::class)->import($chapter, $zipPath);

    $page = $chapter->pages()->first();

    expect($page->width)->toBe(40)
        ->and($page->height)->toBe(10);

    unlink($zipPath);
    unlink($directory.'/1.png');
    rmdir($directory);
});
