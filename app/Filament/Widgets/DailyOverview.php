<?php

namespace App\Filament\Widgets;

use App\Enums\CoinCodeStatus;
use App\Enums\SupportThreadStatus;
use App\Filament\Resources\CoinCodeResource;
use App\Filament\Resources\CoinPackageResource;
use App\Filament\Resources\DownloadResource;
use App\Filament\Resources\GameResource;
use App\Filament\Resources\GameUnlockResource;
use App\Filament\Resources\SupportThreadResource;
use App\Filament\Resources\UserResource;
use App\Models\CoinCode;
use App\Models\CoinPackageClick;
use App\Models\Download;
use App\Models\Game;
use App\Models\GameUnlock;
use App\Models\SupportThread;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DailyOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = now()->startOfDay();

        $clicksToday = CoinPackageClick::query()->where('created_at', '>=', $today)->count();
        $clicksYesterday = CoinPackageClick::query()
            ->where('created_at', '>=', $today->copy()->subDay())
            ->where('created_at', '<', $today)
            ->count();

        $codesToday = CoinCode::query()
            ->where('status', CoinCodeStatus::Used)
            ->where('used_at', '>=', $today)
            ->count();
        $codesYesterday = CoinCode::query()
            ->where('status', CoinCodeStatus::Used)
            ->where('used_at', '>=', $today->copy()->subDay())
            ->where('used_at', '<', $today)
            ->count();

        $unlocksToday = GameUnlock::query()->where('unlocked_at', '>=', $today)->count();
        $unlocksYesterday = GameUnlock::query()
            ->where('unlocked_at', '>=', $today->copy()->subDay())
            ->where('unlocked_at', '<', $today)
            ->count();

        $openSupport = SupportThread::query()->where('status', '!=', SupportThreadStatus::Closed)->count();
        $unread = SupportThread::query()->where('unread_by_admin', true)->count();

        $usersToday = User::query()->where('created_at', '>=', $today)->count();
        $downloadsToday = Download::query()->where('created_at', '>=', $today)->count();
        $published = Game::query()->where('is_published', true)->count();
        $drafts = Game::query()->where('is_published', false)->count();
        $unusedCodes = CoinCode::query()->where('status', CoinCodeStatus::Unused)->count();
        $coinInCirculation = (int) User::query()->sum('coin_balance');

        return [
            Stat::make('Paket tıklaması', (string) $clicksToday)
                ->description($this->delta($clicksToday, $clicksYesterday))
                ->descriptionIcon($clicksToday >= $clicksYesterday ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color('warning')
                ->url(CoinPackageResource::getUrl()),
            Stat::make('Kullanılan kod', (string) $codesToday)
                ->description($this->delta($codesToday, $codesYesterday).' · '.$unusedCodes.' bekliyor')
                ->color('success')
                ->url(CoinCodeResource::getUrl()),
            Stat::make('Açılan oyun', (string) $unlocksToday)
                ->description($this->delta($unlocksToday, $unlocksYesterday))
                ->color('info')
                ->url(GameUnlockResource::getUrl()),
            Stat::make('Açık destek', (string) $openSupport)
                ->description($unread.' okunmamış')
                ->color($unread > 0 ? 'danger' : 'gray')
                ->url(SupportThreadResource::getUrl()),
            Stat::make('Yeni üye', (string) $usersToday)
                ->description('Bugün kayıt · dolaşımda '.number_format($coinInCirculation, 0, ',', '.').' coin')
                ->color('primary')
                ->url(UserResource::getUrl()),
            Stat::make('İndirme', (string) $downloadsToday)
                ->description('Bugün')
                ->url(DownloadResource::getUrl()),
            Stat::make('Yayındaki oyun', (string) $published)
                ->description($drafts.' taslak')
                ->url(GameResource::getUrl()),
        ];
    }

    private function delta(int $today, int $yesterday): string
    {
        $diff = $today - $yesterday;

        if ($diff === 0) {
            return 'Dünle aynı';
        }

        return ($diff > 0 ? '+' : '').$diff.' düne göre';
    }
}
