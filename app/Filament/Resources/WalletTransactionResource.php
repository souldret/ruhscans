<?php

namespace App\Filament\Resources;

use App\Enums\LedgerDirection;
use App\Filament\Resources\WalletTransactionResource\Pages;
use App\Filament\Support\AdminAccess;
use App\Models\WalletTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Cüzdan İşlemleri';

    protected static ?string $modelLabel = 'Cüzdan işlemi';

    protected static ?string $pluralModelLabel = 'Cüzdan işlemleri';

    protected static ?string $navigationGroup = 'Ekonomi';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return AdminAccess::admin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Tarih')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('user.email')->label('Üye')->searchable(),
                Tables\Columns\TextColumn::make('yon')->label('Yön')->badge()->formatStateUsing(fn ($state) => $state instanceof LedgerDirection ? ($state === LedgerDirection::Kredi ? 'Kredi' : 'Borç') : (string) $state),
                Tables\Columns\TextColumn::make('miktar')->label('Miktar'),
                Tables\Columns\TextColumn::make('bakiye_sonrasi')->label('Bakiye'),
                Tables\Columns\TextColumn::make('sebep')->label('Sebep')->searchable()->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('yon')
                    ->label('Yön')
                    ->options([
                        LedgerDirection::Kredi->value => 'Kredi',
                        LedgerDirection::Borc->value => 'Borç',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
        ];
    }
}
