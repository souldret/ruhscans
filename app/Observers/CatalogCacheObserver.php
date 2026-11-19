<?php

namespace App\Observers;

use App\Services\Catalog\CatalogCache;

class CatalogCacheObserver
{
    public function saved(object $model): void
    {
        $this->forget();
    }

    public function deleted(object $model): void
    {
        $this->forget();
    }

    private function forget(): void
    {
        app(CatalogCache::class)->forget();
    }
}
