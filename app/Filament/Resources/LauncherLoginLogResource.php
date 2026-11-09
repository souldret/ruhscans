<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LauncherLoginLogResource\Pages;
use App\Models\LauncherLoginLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LauncherLoginLogResource extends Resource
{
    protected static ?string $model = LauncherLoginLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Başlatıcı';

    protected static ?string $modelLabel = 'Giriş kaydı';

    protected static ?string $pluralModelLabel = 'Giriş kayıtları';

    protected static ?string $navigationLabel = 'Giriş kayıtları';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'game']))
            ->columns([
                Tables\Columns\TextColumn::make('user.email')->label('Kullanıcı')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('game.title')->label('Oyun')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('ip_hash')->label('IP hash')->limit(16),
                Tables\Columns\TextColumn::make('result')->label('Sonuç')->badge(),
                Tables\Columns\TextColumn::make('reason')->label('Neden'),
                Tables\Columns\TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('result')
                    ->label('Sonuç')
                    ->options([
                        'allowed' => 'allowed',
                        'denied' => 'denied',
                        'rate_limited' => 'rate_limited',
                        'locked' => 'locked',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLauncherLoginLogs::route('/'),
        ];
    }
}
