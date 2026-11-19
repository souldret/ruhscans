<?php

namespace App\Services\Reader;

use App\Models\Page;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PageUrlFactory
{
    /**
     * Kısa ömürlü imzalı adres. R2/S3 imzalayamazsa yerel disk adresi döner.
     * Güvenlik fazında yalnızca presigned kalacak.
     */
    public function temporary(Page $page): string
    {
        $diskName = (string) config('content.image_disk', 'r2');
        $minutes = (int) config('content.page_url_ttl', 15);
        $disk = Storage::disk($diskName);

        try {
            return $disk->temporaryUrl($page->r2_path, now()->addMinutes($minutes));
        } catch (Throwable) {
            return $disk->url($page->r2_path);
        }
    }
}
