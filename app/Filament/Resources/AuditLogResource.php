<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Denetim kaydı';

    protected static ?string $modelLabel = 'Kayıt';

    protected static ?string $pluralModelLabel = 'Denetim kaydı';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('action')->label('İşlem')->searchable(),
                Tables\Columns\TextColumn::make('actor.email')->label('Yapan')->placeholder('—'),
                Tables\Columns\TextColumn::make('subject_type')->label('Konu')->toggleable(),
                Tables\Columns\TextColumn::make('subject_id')->label('No'),
                Tables\Columns\TextColumn::make('reason')->label('Sebep')->wrap()->placeholder('—'),
                Tables\Columns\TextColumn::make('ip')->label('IP')->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
