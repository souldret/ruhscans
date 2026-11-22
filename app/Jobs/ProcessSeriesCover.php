<?php

namespace App\Jobs;

use App\Models\Series;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

/**
 * Yüklenen orijinal kapaktan CDN için WebP varyantları üretir, eski dosyaları siler.
 */
class ProcessSeriesCover implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public function __construct(
        public int $seriesId,
        public ?string $sourceDisk,
        public ?string $sourcePath,
        public ?string $previousPath = null,
    ) {}

    public function handle(): void
    {
        $series = Series::query()->find($this->seriesId);

        if ($series === null) {
            if ($this->sourcePath !== null && $this->sourceDisk !== null) {
                Storage::disk($this->sourceDisk)->delete($this->sourcePath);
            }

            return;
        }

        if ($this->sourcePath === null || $this->sourceDisk === null) {
            if ($this->previousPath !== null) {
                $this->deleteVariant($this->previousPath);
            }

            return;
        }

        $contents = Storage::disk($this->sourceDisk)->get($this->sourcePath);

        if ($contents === null || $contents === '') {
            return;
        }

        $manager = new ImageManager(new Driver);
        $quality = (int) config('content.webp_quality', 82);
        $ttl = (int) config('content.cover_cache_ttl', 31536000);
        $options = [
            'ContentType' => 'image/webp',
            'CacheControl' => 'public, max-age='.$ttl.', immutable',
        ];

        $directory = 'covers/'.$series->id;
        $variants = [
            'cover.webp' => [(int) config('content.cover_width', 600), (int) config('content.cover_height', 900), 0],
            'cover-small.webp' => [(int) config('content.cover_small_width', 300), (int) config('content.cover_small_height', 450), 0],
            'cover-placeholder.webp' => [(int) config('content.cover_placeholder_width', 20), (int) config('content.cover_placeholder_height', 30), 4],
        ];

        foreach ($variants as $filename => [$width, $height, $blur]) {
            $image = $manager->read($contents)->cover($width, $height);

            if ($blur > 0) {
                $image->blur($blur);
            }

            Storage::disk(config('content.image_disk'))->put(
                $directory.'/'.$filename,
                (string) $image->toWebp($quality),
                $options,
            );
        }

        $newPath = $directory.'/cover.webp';
        $oldPath = $series->cover_path;

        $series->update(['cover_path' => $newPath]);

        foreach (array_unique(array_filter([$oldPath, $this->previousPath])) as $path) {
            if ($path !== $newPath) {
                $this->deleteVariant($path);
            }
        }

        if ($this->sourcePath !== $newPath) {
            Storage::disk($this->sourceDisk)->delete($this->sourcePath);
        }
    }

    private function deleteVariant(string $path): void
    {
        $disk = Storage::disk(config('content.image_disk'));
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $base = $extension === '' ? $path : substr($path, 0, -(strlen($extension) + 1));

        $disk->delete([
            $path,
            $base.'-small.'.$extension,
            $base.'-placeholder.'.$extension,
        ]);
    }
}
