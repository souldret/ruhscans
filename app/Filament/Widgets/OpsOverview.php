<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Services\Ops\AdminDigest;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    protected function getStats(): array
    {
        $digest = app(AdminDigest::class)->today();

        return [
            Stat::make('Günlük okuma', (string) $digest['readings'])
                ->description('Bugün ilerleme kaydı'),
            Stat::make('Yeni üye', (string) $digest['members'])
                ->description('Bugün açılan hesap'),
            Stat::make('Kod kullanımı', (string) $digest['codes'])
                ->description('Bugün kullanılan kod'),
            Stat::make('Şikayet kuyruğu', (string) $digest['reports'])
                ->description('Bekleyen bildirim'),
        ];
    }
}
