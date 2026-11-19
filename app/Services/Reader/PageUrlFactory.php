<?php

namespace App\Services\Reader;

use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class PageUrlFactory
{
    /**
     * Yetki kontrolünden sonra çağrılır. Adres en fazla 5 dakika yaşar.
     */
    public function temporary(Page $page): string
    {
        $minutes = min(5, max(1, (int) config('security.page_url_ttl', 5)));
        $expires = now()->addMinutes($minutes);
        $diskName = (string) config('content.image_disk', 'r2');
        $disk = Storage::disk($diskName);

        if ($disk->getAdapter() instanceof \League\Flysystem\AwsS3V3\AwsS3V3Adapter
            && method_exists($disk, 'providesTemporaryUrls')
            && $disk->providesTemporaryUrls()) {
            return $disk->temporaryUrl($page->r2_path, $expires);
        }

        return URL::temporarySignedRoute('reader.media', $expires, ['page' => $page->id]);
    }

    public function stream(Page $page)
    {
        $diskName = (string) config('content.image_disk', 'r2');
        $disk = Storage::disk($diskName);

        abort_unless($disk->exists($page->r2_path), 404);

        return $disk->response($page->r2_path, null, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'private, max-age=60',
            'Content-Disposition' => 'inline',
        ]);
    }
}
