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
        if (! $this->announcementEnabled()) {
            return null;
        }

        $text = trim((string) SiteSetting::get('announcement'));

        return $text === '' ? null : $text;
    }

    public function announcementEnabled(): bool
    {
        return SiteSetting::get('announcement_enabled', '1') !== '0';
    }

    public function watermark(): bool
    {
        $stored = SiteSetting::get('watermark_enabled');

        return $stored === null
            ? (bool) config('security.watermark')
            : $stored === '1';
    }

    public function ageGateText(): string
    {
        $text = trim((string) SiteSetting::get('age_gate_text'));

        return $text !== '' ? $text : 'Bu site yalnızca 18 yaş ve üzeri içindir. Devam ederek yetişkin olduğunuzu onaylarsınız.';
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
