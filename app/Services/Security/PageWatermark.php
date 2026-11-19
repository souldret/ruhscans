<?php

namespace App\Services\Security;

use App\Models\SiteSetting;
use App\Models\User;

class PageWatermark
{
    public function enabled(): bool
    {
        $stored = SiteSetting::get('watermark_enabled');

        if ($stored === null) {
            return (bool) config('security.watermark');
        }

        return $stored === '1';
    }

    public function text(?User $user): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        if ($user === null) {
            return 'ruhscans';
        }

        return 'ruhscans #'.$user->getAuthIdentifier();
    }
}
