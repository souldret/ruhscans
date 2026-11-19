<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\LedgerDirection;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';

    protected static ?string $title = 'Cüzdan hareketleri';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('yon')->label('Yön')->formatStateUsing(fn ($state) => $state instanceof LedgerDirection ? ($state === LedgerDirection::Kredi ? 'Kredi' : 'Borç') : (string) $state),
                Tables\Columns\TextColumn::make('miktar')->label('Miktar'),
                Tables\Columns\TextColumn::make('bakiye_sonrasi')->label('Bakiye'),
                Tables\Columns\TextColumn::make('sebep')->label('Sebep')->wrap(),
            ]);
    }
}
