<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GameUnlockResource\Pages;
use App\Models\GameUnlock;
use App\Models\User;
use App\Services\GameUnlocker;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameUnlockResource extends Resource
{
    protected static ?string $model = GameUnlock::class;

    protected static ?string $navigationIcon = 'heroicon-o-lock-open';

    protected static ?string $navigationGroup = 'Coin';

    protected static ?string $modelLabel = 'Oyun kilidi';

    protected static ?string $pluralModelLabel = 'Oyun kilitleri';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('Üye')->searchable(),
                Tables\Columns\TextColumn::make('game.title')->label('Oyun')->searchable(),
                Tables\Columns\TextColumn::make('coins_paid')->label('Ödenen coin'),
                Tables\Columns\TextColumn::make('unlocked_at')->label('Açılış')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('unlocked_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('refund')
                    ->label('İade et')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Sebep')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (GameUnlock $record, array $data, GameUnlocker $unlocker): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);

                        $unlocker->refund($actor, $record, $data['reason']);

                        Notification::make()->title('İade edildi')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameUnlocks::route('/'),
        ];
    }
}
