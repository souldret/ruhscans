<?php

namespace App\Filament\Widgets;

use App\Enums\CodeStatus;
use App\Filament\Support\AdminAccess;
use App\Models\RedeemCode;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CodeUsage extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected static ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        return AdminAccess::admin();
    }

    protected function getStats(): array
    {
        $counts = RedeemCode::query()
            ->selectRaw('durum, count(*) as adet')
            ->groupBy('durum')
            ->pluck('adet', 'durum');

        return [
            Stat::make('Aktif kod', (string) ($counts[CodeStatus::Aktif->value] ?? 0)),
            Stat::make('Kullanılan kod', (string) ($counts[CodeStatus::Kullanildi->value] ?? 0)),
            Stat::make('İptal kod', (string) ($counts[CodeStatus::Iptal->value] ?? 0)),
        ];
    }
}
