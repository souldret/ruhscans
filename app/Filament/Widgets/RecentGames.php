<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\GameResource;
use App\Models\Game;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentGames extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Son oyunlar';

    public function table(Table $table): Table
    {
        return $table
            ->query(Game::query()->latest('updated_at'))
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')->label('Kapak')->disk('public')->height(48),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('coin_price')->label('Coin')->formatStateUsing(fn ($state): string => (int) $state === 0 ? 'Ücretsiz' : (string) $state),
                Tables\Columns\TextColumn::make('sale_price')->label('İndirim')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_published')->label('Yayın')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('Güncelleme')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Düzenle')
                    ->icon('heroicon-m-pencil-square')
                    ->url(fn (Game $record): string => GameResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
