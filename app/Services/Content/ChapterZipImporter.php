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
                $maxWidth = (int) config('content.max_page_width', 1600);

                if ($maxWidth > 0 && $image->width() > $maxWidth) {
                    $image->scale(width: $maxWidth);
                }

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

        $maxFiles = (int) config('content.max_zip_files', 500);
        $maxUncompressed = (int) config('content.max_zip_uncompressed_kilobytes', 512000) * 1024;
        $maxRatio = (int) config('content.max_zip_compression_ratio', 200);
        $allowedExtensions = (array) config('content.zip_allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

        if ($zip->numFiles > $maxFiles) {
            $zip->close();
            throw new RuntimeException('ZIP dosya sayısı sınırı aşıldı (en fazla '.$maxFiles.').');
        }

        $safeNames = [];
        $failures = [];
        $total = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (! is_array($stat)) {
                continue;
            }

            $name = (string) $stat['name'];
            $base = basename(str_replace('\\', '/', $name));

            if (! $this->isSafeName($name) || str_starts_with($base, '.') || str_contains($name, '__MACOSX')) {
                $failures[] = ['file' => $base, 'message' => 'Güvenli olmayan dosya yolu.'];

                continue;
            }

            if (str_ends_with($name, '/')) {
                continue;
            }

            $extension = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if (! in_array($extension, $allowedExtensions, true)) {
                $failures[] = ['file' => $base, 'message' => 'Desteklenmeyen dosya uzantısı: '.$extension];

                continue;
            }

            $size = (int) ($stat['size'] ?? 0);
            $compressed = (int) ($stat['comp_size'] ?? 0);
            $total += $size;

            if ($total > $maxUncompressed) {
                $zip->close();
                throw new RuntimeException('ZIP açılmamış boyut sınırı aşıldı.');
            }

            if ($compressed > 0 && $size / $compressed > $maxRatio) {
                $failures[] = ['file' => $base, 'message' => 'Sıkıştırma oranı şüpheli (zip bombası).'];

                continue;
            }

            $safeNames[] = $name;
        }

        $directory = storage_path('app/chapter-imports/'.uniqid('zip_', true));
        mkdir($directory, 0777, true);
        $zip->extractTo($directory, $safeNames);
        $zip->close();

        $images = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $name = $file->getFilename();
            $mime = mime_content_type($file->getPathname()) ?: '';
            $kilobytes = (int) ceil($file->getSize() / 1024);

            if (! in_array($mime, $allowedMimes, true)) {
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

    private function isSafeName(string $name): bool
    {
        if ($name === '' || str_contains($name, "\0")) {
            return false;
        }

        if (str_starts_with($name, '/') || preg_match('#^[A-Za-z]:[\\\\/]#', $name)) {
            return false;
        }

        foreach (explode('/', str_replace('\\', '/', $name)) as $segment) {
            if ($segment === '..') {
                return false;
            }
        }

        return true;
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
