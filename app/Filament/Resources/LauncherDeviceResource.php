<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LauncherDeviceResource\Pages;
use App\Models\LauncherDevice;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LauncherDeviceResource extends Resource
{
    protected static ?string $model = LauncherDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationGroup = 'Başlatıcı';

    protected static ?string $modelLabel = 'Cihaz';

    protected static ?string $pluralModelLabel = 'Cihazlar';

    protected static ?string $navigationLabel = 'Cihazlar';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'game']))
            ->columns([
                Tables\Columns\TextColumn::make('user.email')->label('Kullanıcı')->searchable(),
                Tables\Columns\TextColumn::make('game.title')->label('Oyun')->searchable(),
                Tables\Columns\TextColumn::make('device_id')->label('Cihaz')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('last_launcher_version')->label('Sürüm'),
                Tables\Columns\TextColumn::make('ip_hash')->label('IP hash')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('first_seen_at')->label('İlk')->dateTime('d.m.Y H:i'),
                Tables\Columns\TextColumn::make('last_seen_at')->label('Son')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('last_seen_at', 'desc')
            ->actions([
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLauncherDevices::route('/'),
        ];
    }
}
