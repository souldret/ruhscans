<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoinPackageResource\Pages;
use App\Models\CoinPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CoinPackageResource extends Resource
{
    protected static ?string $model = CoinPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Coin';

    protected static ?string $modelLabel = 'Coin paketi';

    protected static ?string $pluralModelLabel = 'Coin paketleri';

    public static function httpsRule(): string
    {
        return 'regex:/^https:\/\/.+/';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Ad')->required()->maxLength(255),
            Forms\Components\TextInput::make('coin_amount')->label('Coin')->numeric()->minValue(1)->integer()->required(),
            Forms\Components\TextInput::make('bonus_coin')->label('Bonus coin')->numeric()->minValue(0)->integer()->default(0)->required(),
            Forms\Components\TextInput::make('price_label')->label('Fiyat yazısı')->placeholder('99 TL')->required()->maxLength(50),
            Forms\Components\TextInput::make('url')
                ->label('Bağlantı')
                ->required()
                ->maxLength(255)
                ->rule(self::httpsRule())
                ->helperText('Yalnızca https adresi kabul edilir.'),
            Forms\Components\TextInput::make('button_text')->label('Buton')->default('Satın Al')->required()->maxLength(50),
            Forms\Components\TextInput::make('badge')->label('Rozet')->placeholder('En Popüler')->maxLength(50),
            Forms\Components\Textarea::make('description')->label('Açıklama')->rows(3)->columnSpanFull(),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->integer()->default(0)->required(),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
            Forms\Components\Toggle::make('is_featured')->label('Öne çıkan'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount([
                'clicks as clicks_today' => fn ($query) => $query->where('created_at', '>=', now()->startOfDay()),
                'clicks as clicks_week' => fn ($query) => $query->where('created_at', '>=', now()->subDays(7)),
            ]))
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('coin_amount')->label('Coin')->sortable(),
                Tables\Columns\TextColumn::make('bonus_coin')->label('Bonus'),
                Tables\Columns\TextColumn::make('price_label')->label('Fiyat'),
                Tables\Columns\TextColumn::make('clicks_today')->label('Bugün'),
                Tables\Columns\TextColumn::make('clicks_week')->label('7 gün'),
                Tables\Columns\IconColumn::make('is_active')->label('Aktif')->boolean(),
                Tables\Columns\IconColumn::make('is_featured')->label('Öne çıkan')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoinPackages::route('/'),
            'create' => Pages\CreateCoinPackage::route('/yeni'),
            'edit' => Pages\EditCoinPackage::route('/{record}/duzenle'),
        ];
    }
}
