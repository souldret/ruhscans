<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CoinPackageResource;
use App\Models\CoinPackage;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class CoinPackagesSnapshot extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Coin paketleri';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CoinPackage::query()
                    ->withCount([
                        'clicks as clicks_today' => fn ($query) => $query->where('created_at', '>=', now()->startOfDay()),
                        'clicks as clicks_week' => fn ($query) => $query->where('created_at', '>=', now()->subDays(7)),
                    ])
                    ->orderBy('sort_order')
            )
            ->paginated(false)
            ->emptyStateHeading('Paket tanımlı değil')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Paket'),
                Tables\Columns\TextColumn::make('coin_amount')->label('Coin'),
                Tables\Columns\TextColumn::make('bonus_coin')->label('Bonus'),
                Tables\Columns\TextColumn::make('price_label')->label('Fiyat'),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\IconColumn::make('is_featured')->label('Öne çıkan')->boolean(),
                Tables\Columns\TextColumn::make('clicks_today')->label('Bugün'),
                Tables\Columns\TextColumn::make('clicks_week')->label('7 gün'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('manage')
                    ->label('Paketleri yönet')
                    ->url(CoinPackageResource::getUrl()),
            ]);
    }
}
