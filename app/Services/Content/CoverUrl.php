<?php

namespace App\Services\Content;

use App\Models\Series;
use Illuminate\Support\Facades\Storage;

/**
 * Kapak görsellerini CDN (R2_URL) üzerinden, varyant adıyla servis eder.
 */
class CoverUrl
{
    public function for(Series $series, string $variant = 'cover'): ?string
    {
        if (blank($series->cover_path)) {
            return null;
        }

        $path = $variant === 'cover'
            ? $series->cover_path
            : $this->variantPath($series->cover_path, $variant);

        $disk = (string) config('content.image_disk');

        // R2 anahtarları tanımlı değilken adapter'ı instantiate etmek bucket hatası verir.
        if ($disk === 'r2' && blank(config('filesystems.disks.r2.bucket'))) {
            return null;
        }

        $base = $this->baseUrl($disk);

        if ($base !== null) {
            return rtrim($base, '/').'/'.ltrim($path, '/');
        }

        return Storage::disk($disk)->url($path);
    }

    private function variantPath(string $path, string $variant): string
    {
        $suffix = match ($variant) {
            'small' => '-small',
            'placeholder' => '-placeholder',
            default => '',
        };

        if ($suffix === '') {
            return $path;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $base = $extension === '' ? $path : substr($path, 0, -(strlen($extension) + 1));

        return $base.$suffix.($extension === '' ? '' : '.'.$extension);
    }

    private function baseUrl(string $disk): ?string
    {
        if ($disk !== 'r2') {
            return null;
        }

        $url = config('content.cdn_url') ?: config('filesystems.disks.r2.url');

        if (blank($url)) {
            return null;
        }

        $url = (string) $url;

        return preg_match('#^https?://#i', $url) ? $url : 'https://'.$url;
    }
}
