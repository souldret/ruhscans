<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\CoinTransactionResource;
use App\Models\CoinTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentCoinActivity extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Son coin hareketleri';

    public function table(Table $table): Table
    {
        return $table
            ->query(CoinTransaction::query()->with('user')->latest('created_at'))
            ->paginated([8])
            ->defaultPaginationPageOption(8)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Zaman')->since(),
                Tables\Columns\TextColumn::make('user.name')->label('Üye')->placeholder('—')->limit(18),
                Tables\Columns\TextColumn::make('type')->label('Tür')->formatStateUsing(fn ($state) => $state?->label() ?? '—'),
                Tables\Columns\TextColumn::make('amount')->label('Miktar')->formatStateUsing(function ($state): string {
                    $value = (int) $state;

                    return ($value > 0 ? '+' : '').number_format($value, 0, ',', '.');
                }),
                Tables\Columns\TextColumn::make('balance_after')->label('Bakiye'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('all')
                    ->label('Tümü')
                    ->url(CoinTransactionResource::getUrl()),
            ]);
    }
}
