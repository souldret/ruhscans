<?php

namespace App\Filament\Resources;

use App\Enums\CoinCodeStatus;
use App\Filament\Resources\CoinCodeResource\Pages;
use App\Models\CoinCode;
use App\Models\User;
use App\Services\CoinClawback;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoinCodeResource extends Resource
{
    protected static ?string $model = CoinCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Coin';

    protected static ?string $modelLabel = 'Coin kodu';

    protected static ?string $pluralModelLabel = 'Coin kodları';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kod')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('batch.name')->label('Parti'),
                Tables\Columns\TextColumn::make('coin_amount')->label('Coin'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->formatStateUsing(fn (CoinCodeStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('user.name')->label('Kullanan'),
                Tables\Columns\TextColumn::make('used_at')->label('Kullanım')->dateTime('d.m.Y H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(collect(CoinCodeStatus::cases())->mapWithKeys(
                        fn (CoinCodeStatus $status) => [$status->value => $status->label()]
                    )),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\Action::make('disable')
                    ->label('İptal et')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (CoinCode $record) => $record->status === CoinCodeStatus::Unused)
                    ->action(function (CoinCode $record, CoinClawback $clawback): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        $clawback->disable($actor, $record, 'Kod iptal edildi');
                    }),
                Tables\Actions\Action::make('reclaim')
                    ->label('Satış iadesi')
                    ->color('warning')
                    ->visible(fn (CoinCode $record) => $record->status !== CoinCodeStatus::Disabled || $record->used_by !== null)
                    ->form([
                            Forms\Components\Textarea::make('reason')->label('Sebep')->required()->maxLength(255),
                        ])
                    ->action(function (CoinCode $record, array $data, CoinClawback $clawback): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        $result = $clawback->reclaim($actor, $record, $data['reason']);
                        $title = match ($result) {
                            'reclaimed' => 'Coin geri alındı',
                            'review' => 'Bakiye yetmedi, inceleme açıldı',
                            default => 'Kod iptal edildi',
                        };
                        Notification::make()->title($title)->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoinCodes::route('/'),
        ];
    }
}
