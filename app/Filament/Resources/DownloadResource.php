<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownloadResource\Pages;
use App\Models\Download;
use App\Models\Game;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationGroup = 'Coin';

    protected static ?string $modelLabel = 'İndirme';

    protected static ?string $pluralModelLabel = 'İndirmeler';

    protected static ?string $navigationLabel = 'İndirmeler';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'gameFile.game']))
            ->columns([
                Tables\Columns\TextColumn::make('gameFile.game.title')->label('Oyun')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('Üye')->searchable(),
                Tables\Columns\TextColumn::make('gameFile.label')->label('Dosya'),
                Tables\Columns\TextColumn::make('ip')->label('IP'),
                Tables\Columns\TextColumn::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('game')
                    ->label('Oyun')
                    ->options(fn () => Game::query()->orderBy('title')->pluck('title', 'id'))
                    ->query(function ($query, array $data) {
                        if (blank($data['value'] ?? null)) {
                            return;
                        }

                        $query->whereHas('gameFile', fn ($files) => $files->where('game_id', $data['value']));
                    }),
                Tables\Filters\SelectFilter::make('user')
                    ->label('Üye')
                    ->relationship('user', 'email'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDownloads::route('/'),
        ];
    }
}
