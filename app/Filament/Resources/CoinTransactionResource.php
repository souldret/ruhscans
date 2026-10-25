<?php

namespace App\Filament\Resources;

use App\Enums\CoinTransactionType;
use App\Filament\Resources\CoinTransactionResource\Pages;
use App\Models\CoinTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoinTransactionResource extends Resource
{
    protected static ?string $model = CoinTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Coin';

    protected static ?string $modelLabel = 'Coin hareketi';

    protected static ?string $pluralModelLabel = 'Coin hareketleri';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Üye')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tür')
                    ->formatStateUsing(fn (CoinTransactionType $state) => $state->label()),
                Tables\Columns\TextColumn::make('amount')->label('Tutar')->sortable(),
                Tables\Columns\TextColumn::make('balance_after')->label('Sonraki bakiye'),
                Tables\Columns\TextColumn::make('note')->label('Not')->limit(40),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoinTransactions::route('/'),
        ];
    }
}
