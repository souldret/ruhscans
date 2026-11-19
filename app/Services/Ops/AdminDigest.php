<?php

namespace App\Services\Ops;

use App\Enums\ReportStatus;
use App\Models\LibraryEntry;
use App\Models\RedeemCode;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class AdminDigest
{
    /**
     * @return array{readings: int, members: int, codes: int, reports: int, vip: int, queue: int, failed: int}
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
                'vip' => User::query()->where('vip_bitis', '>', now())->count(),
                'queue' => $this->count('jobs'),
                'failed' => $this->count('failed_jobs'),
            ];
        });
    }

    private function count(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (Throwable) {
            return 0;
        }
    }
}
