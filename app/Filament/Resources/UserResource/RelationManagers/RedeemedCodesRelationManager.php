<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Enums\CodeKind;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RedeemedCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'redeemedCodes';

    protected static ?string $title = 'Kullanılan kodlar';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('batch'))
            ->columns([
                Tables\Columns\TextColumn::make('batch.ad')->label('Parti'),
                Tables\Columns\TextColumn::make('tur')->label('Tür')->formatStateUsing(fn ($state) => $state instanceof CodeKind ? $state->label() : (string) $state),
                Tables\Columns\TextColumn::make('deger')->label('Değer'),
                Tables\Columns\TextColumn::make('son_dort')->label('Son 4'),
                Tables\Columns\TextColumn::make('kullanim_tarihi')->label('Tarih')->dateTime('d.m.Y H:i'),
            ]);
    }
}
