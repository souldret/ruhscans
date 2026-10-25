<?php

namespace App\Filament\Resources;

use App\Enums\CoinCodeStatus;
use App\Filament\Resources\CoinCodeBatchResource\Pages;
use App\Models\CoinCodeBatch;
use App\Models\User;
use App\Services\CoinCodeIssuer;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoinCodeBatchResource extends Resource
{
    protected static ?string $model = CoinCodeBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Coin';

    protected static ?string $modelLabel = 'Kod partisi';

    protected static ?string $pluralModelLabel = 'Kod partileri';

    public static function canCreate(): bool
    {
        return true;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'codes as used_count' => fn ($query) => $query->where('status', CoinCodeStatus::Used->value),
                'codes as remaining_count' => fn ($query) => $query->where('status', CoinCodeStatus::Unused->value),
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('coin_amount')->label('Coin'),
                Tables\Columns\TextColumn::make('quantity')->label('Toplam'),
                Tables\Columns\TextColumn::make('used_count')->label('Kullanılan'),
                Tables\Columns\TextColumn::make('remaining_count')->label('Kalan'),
                Tables\Columns\TextColumn::make('coin_value')
                    ->label('Toplam coin')
                    ->state(fn (CoinCodeBatch $record) => $record->quantity * $record->coin_amount),
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('generate')
                    ->label('Kod Üret')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('name')->label('Parti adı')->required()->maxLength(120),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Adet')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue(1000)
                            ->default(10),
                        Forms\Components\TextInput::make('coin_amount')
                            ->label('Coin miktarı')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(100),
                        Forms\Components\TextInput::make('prefix')
                            ->label('Ön ek')
                            ->maxLength(8)
                            ->helperText('İsteğe bağlı. Karışan harfler kullanılmaz.'),
                    ])
                    ->action(function (array $data, CoinCodeIssuer $issuer): void {
                        $admin = auth()->user();
                        abort_unless($admin instanceof User, 403);

                        $batch = $issuer->issue(
                            $admin,
                            $data['name'],
                            (int) $data['quantity'],
                            (int) $data['coin_amount'],
                            filled($data['prefix'] ?? null) ? (string) $data['prefix'] : null,
                        );

                        Notification::make()->title($batch->quantity.' kod üretildi')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('txt')
                    ->label('TXT')
                    ->url(fn (CoinCodeBatch $record) => route('admin.coin-code-batches.export', [$record, 'txt']))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('csv')
                    ->label('CSV')
                    ->url(fn (CoinCodeBatch $record) => route('admin.coin-code-batches.export', [$record, 'csv']))
                    ->openUrlInNewTab(),
            ])
            ->defaultSort('id', 'desc')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoinCodeBatches::route('/'),
        ];
    }
}
