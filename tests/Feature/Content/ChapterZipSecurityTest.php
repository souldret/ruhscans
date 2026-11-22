<?php

use App\Models\Chapter;
use App\Services\Content\ChapterZipImporter;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

function zipSecurityImage(): string
{
    return (string) (new ImageManager(new Driver))->create(20, 30)->fill('#112233')->toPng();
}

function makeZip(string $path, array $entries): void
{
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $contents) {
        $zip->addFromString($name, $contents);
    }

    $zip->close();
}

test('zip slip girişimi reddedilir', function () {
    Storage::fake('local');

    $directory = storage_path('app/zip-security-'.uniqid());
    mkdir($directory);
    $zipPath = $directory.'/slip.zip';
    makeZip($zipPath, ['../disa.png' => zipSecurityImage()]);

    $chapter = Chapter::factory()->create();
    $upload = app(ChapterZipImporter::class)->import($chapter, $zipPath);

    expect($upload->failures)->not->toBeEmpty()
        ->and($upload->failures[0]['message'])->toBe('Güvenli olmayan dosya yolu.')
        ->and($chapter->pages()->count())->toBe(0);

    unlink($zipPath);
    rmdir($directory);
});

test('php ve svg uzantıları reddedilir', function () {
    Storage::fake('local');

    $directory = storage_path('app/zip-security-'.uniqid());
    mkdir($directory);
    $zipPath = $directory.'/dangerous.zip';
    makeZip($zipPath, [
        'shell.php' => '<?php echo 1;',
        'ikon.svg' => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
    ]);

    $chapter = Chapter::factory()->create();
    $upload = app(ChapterZipImporter::class)->import($chapter, $zipPath);

    expect($upload->failures)->toHaveCount(2)
        ->and($chapter->pages()->count())->toBe(0);

    unlink($zipPath);
    rmdir($directory);
});

test('dosya sayısı sınırı aşılır', function () {
    Storage::fake('local');
    config(['content.max_zip_files' => 2]);

    $directory = storage_path('app/zip-security-'.uniqid());
    mkdir($directory);
    $zipPath = $directory.'/cok.zip';
    makeZip($zipPath, [
        '1.png' => zipSecurityImage(),
        '2.png' => zipSecurityImage(),
        '3.png' => zipSecurityImage(),
    ]);

    $chapter = Chapter::factory()->create();

    expect(fn () => app(ChapterZipImporter::class)->import($chapter, $zipPath))
        ->toThrow(RuntimeException::class, 'dosya sayısı sınırı aşıldı');

    unlink($zipPath);
    rmdir($directory);
});
