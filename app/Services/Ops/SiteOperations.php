<?php

namespace App\Services\Ops;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Artisan;

class SiteOperations
{
    public function maintenance(): bool
    {
        return SiteSetting::get('maintenance_mode') === '1';
    }

    public function panicUrl(): string
    {
        $url = SiteSetting::get('panic_url');

        return filled($url) ? $url : 'https://www.google.com';
    }

    public function sonteklifUrl(): string
    {
        $url = SiteSetting::get('sonteklif_url', (string) config('economy.sonteklif_url'));

        return filled($url) ? $url : (string) config('economy.sonteklif_url');
    }

    public function announcement(): ?string
    {
        $text = trim((string) SiteSetting::get('announcement'));

        return $text === '' ? null : $text;
    }

    public function enableMaintenance(): void
    {
        SiteSetting::put('maintenance_mode', '1');
        Artisan::call('down', [
            '--render' => 'errors.503',
            '--retry' => 60,
        ]);
    }

    public function disableMaintenance(): void
    {
        SiteSetting::put('maintenance_mode', '0');
        Artisan::call('up');
    }
}
