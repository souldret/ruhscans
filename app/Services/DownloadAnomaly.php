<?php

namespace App\Services;

use App\Models\Download;
use App\Models\User;

class DownloadAnomaly
{
    public function inspect(User $user, ?string $country): void
    {
        if (! is_string($country) || strlen($country) !== 2) {
            return;
        }

        $minutes = max(1, (int) config('membership.anomaly_minutes', 30));

        $previous = Download::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->whereNotNull('country')
            ->where('country', '!=', strtoupper($country))
            ->value('country');

        if (! is_string($previous)) {
            return;
        }

        app(AdminAlerter::class)->send(
            'download-country:'.$user->id,
            'İndirme anomalisi',
            $user->email.' hesabı '.$minutes.' dakika içinde '.$previous.' ve '.strtoupper($country).' ülkelerinden indirme yaptı.',
        );
    }
}
