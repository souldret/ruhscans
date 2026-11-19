<?php

namespace App\Services\Ops;

use App\Enums\ReportStatus;
use App\Models\LibraryEntry;
use App\Models\RedeemCode;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminDigest
{
    /**
     * @return array{readings: int, members: int, codes: int, reports: int}
     */
    public function today(): array
    {
        return Cache::remember('admin.digest', 60, function () {
            $start = now()->startOfDay();

            return [
                'readings' => LibraryEntry::query()->where('last_read_at', '>=', $start)->count(),
                'members' => User::query()->where('created_at', '>=', $start)->count(),
                'codes' => RedeemCode::query()->where('kullanim_tarihi', '>=', $start)->count(),
                'reports' => Report::query()->where('status', ReportStatus::Bekliyor)->count(),
            ];
        });
    }
}
