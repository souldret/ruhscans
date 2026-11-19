<?php

namespace App\Services\Content;

use App\Models\Chapter;
use App\Models\ChapterUpload;
use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;
use ZipArchive;

class ChapterZipImporter
{
    public function __construct(
        private readonly NaturalSorter $sorter,
        private readonly WebtoonSlicer $slicer,
    ) {}

    public function import(Chapter $chapter, string $zipPath, ?int $userId = null): ChapterUpload
    {
        $upload = ChapterUpload::query()->create([
            'chapter_id' => $chapter->id,
            'user_id' => $userId,
            'status' => 'isleniyor',
        ]);

        $extracted = $this->extract($zipPath);
        $images = $this->sorter->sort($extracted['images']);
        $failures = $extracted['failures'];
        $upload->update(['total' => count($images) + count($failures)]);

        $disk = config('content.image_disk');
        $manager = new ImageManager(new Driver);
        $order = (int) $chapter->pages()->max('order');
        $processed = count($failures);

        foreach ($images as $path) {
            try {
                $contents = file_get_contents($path);
                $this->assertImage($path, $contents);

                $image = $manager->read($contents);
                foreach ($this->slicer->slice($image) as $slice) {
                    $order++;
                    $encoded = $slice['image']->toWebp((int) config('content.webp_quality'));
                    $key = sprintf('chapters/%d/%d/%04d.webp', $chapter->series_id, $chapter->id, $order);

                    Storage::disk($disk)->put($key, (string) $encoded);

                    Page::query()->create([
                        'chapter_id' => $chapter->id,
                        'order' => $order,
                        'r2_path' => $key,
                        'width' => $slice['width'],
                        'height' => $slice['height'],
                    ]);
                }
            } catch (\Throwable $exception) {
                $failures[] = [
                    'file' => basename($path),
                    'message' => $exception->getMessage(),
                ];
            }

            $processed++;
            $upload->update([
                'processed' => $processed,
                'failures' => $failures,
            ]);
        }

        $this->cleanup($extracted['directory']);

        $upload->update([
            'status' => $failures === [] ? 'tamamlandi' : 'kismi',
            'processed' => $upload->total,
            'failures' => $failures,
        ]);

        return $upload->refresh();
    }

    /**
     * @return array{directory: string, images: list<string>, failures: list<array{file: string, message: string}>}
     */
    private function extract(string $zipPath): array
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('ZIP açılamadı.');
        }

        $directory = storage_path('app/chapter-imports/'.uniqid('zip_', true));
        mkdir($directory, 0777, true);
        $zip->extractTo($directory);
        $zip->close();

        $images = [];
        $failures = [];
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            if (str_starts_with($name, '.') || str_contains($file->getPathname(), '__MACOSX')) {
                continue;
            }

            $mime = mime_content_type($file->getPathname()) ?: '';
            $kilobytes = (int) ceil($file->getSize() / 1024);

            if (! in_array($mime, $allowed, true)) {
                $failures[] = ['file' => $name, 'message' => 'Desteklenmeyen dosya türü: '.$mime];

                continue;
            }

            if ($kilobytes > (int) config('content.max_image_kilobytes')) {
                $failures[] = ['file' => $name, 'message' => 'Dosya boyutu sınırı aşıldı.'];

                continue;
            }

            $images[] = $file->getPathname();
        }

        return compact('directory', 'images', 'failures');
    }

    private function assertImage(string $path, string|false $contents): void
    {
        if ($contents === false || @getimagesizefromstring($contents) === false) {
            throw new RuntimeException(basename($path).' geçerli bir görsel değil.');
        }
    }

    private function cleanup(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
