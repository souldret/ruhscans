<?php

namespace App\Filament\Widgets;

use App\Enums\CoinCodeStatus;
use App\Enums\SupportThreadStatus;
use App\Models\CoinCode;
use App\Models\CoinPackageClick;
use App\Models\GameUnlock;
use App\Models\SupportThread;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DailyOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();

        return [
            Stat::make('Paket tıklaması', (string) CoinPackageClick::query()->where('created_at', '>=', $today)->count())
                ->description('Bugün')
                ->color('warning'),
            Stat::make('Kullanılan kod', (string) CoinCode::query()->where('status', CoinCodeStatus::Used)->where('used_at', '>=', $today)->count())
                ->description('Bugün')
                ->color('success'),
            Stat::make('Açılan oyun', (string) GameUnlock::query()->where('unlocked_at', '>=', $today)->count())
                ->description('Bugün')
                ->color('info'),
            Stat::make('Açık destek', (string) SupportThread::query()->where('status', '!=', SupportThreadStatus::Closed)->count())
                ->description(SupportThread::query()->where('unread_by_admin', true)->count().' okunmamış')
                ->color('danger'),
        ];
    }
}
