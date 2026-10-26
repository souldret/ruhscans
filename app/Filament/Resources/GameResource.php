<?php

namespace App\Filament\Resources;

use App\Enums\Platform;
use App\Enums\TranslationStatus;
use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use App\Support\Slug;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;

    protected static ?string $navigationIcon = 'heroicon-o-play';

    protected static ?string $modelLabel = 'Oyun';

    protected static ?string $pluralModelLabel = 'Oyunlar';

    protected static ?string $navigationLabel = 'Oyunlar';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Oyun')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Başlık')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Slug::from((string) $state))),
                Forms\Components\TextInput::make('slug')
                    ->label('Adres')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('original_title')->label('Özgün ad')->maxLength(255),
                Forms\Components\TextInput::make('developer')->label('Geliştirici')->maxLength(255),
 Forms\Components\DatePicker::make('released_on')->label('Çıkış tarihi')->native(false),
                Forms\Components\Textarea::make('description')->label('Açıklama')->rows(5)->columnSpanFull(),
                Forms\Components\Select::make('tags')
                    ->label('Etiketler')
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
            ])->columns(2),

            Forms\Components\Section::make('Görseller')->schema([
                Forms\Components\FileUpload::make('cover_path')
                    ->label('Kapak')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->rules(['mimetypes:image/jpeg,image/png,image/webp'])
                    ->disk('public')
                    ->directory('games/covers'),
                Forms\Components\FileUpload::make('banner_path')
                    ->label('Banner')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->rules(['mimetypes:image/jpeg,image/png,image/webp'])
                    ->disk('public')
                    ->directory('games/banners'),
                Forms\Components\FileUpload::make('screenshots')
                    ->label('Ekran görüntüleri')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->rules(['mimetypes:image/jpeg,image/png,image/webp'])
                    ->multiple()
                    ->reorderable()
                    ->disk('public')
                    ->directory('games/screenshots')
                    ->columnSpanFull(),
            ])->columns(2),

            Forms\Components\Section::make('Çeviri ve erişim')->schema([
                Forms\Components\TextInput::make('game_version')->label('Oyun sürümü')->maxLength(50),
                Forms\Components\TextInput::make('translation_version')->label('Çeviri sürümü')->maxLength(50),
                Forms\Components\Select::make('translation_status')
                    ->label('Çeviri durumu')
                    ->options(collect(TranslationStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
                Forms\Components\TextInput::make('translation_percent')
                    ->label('Çeviri yüzdesi')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),
                Forms\Components\TextInput::make('size_mb')->label('PC boyutu (MB)')->numeric()->minValue(0)->required(),
                Forms\Components\TextInput::make('apk_size_mb')->label('APK boyutu (MB)')->numeric()->minValue(0),
                Forms\Components\TextInput::make('playtime_hours')->label('Oynanış süresi (saat)')->numeric()->minValue(0)->maxValue(999),
                Forms\Components\TextInput::make('coin_price')
                    ->label('Coin fiyatı')
                    ->helperText('0 ücretsiz demektir.')
                    ->numeric()
                    ->minValue(0)
                    ->integer()
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('sale_price')
                    ->label('İndirimli fiyat')
                    ->numeric()
                    ->minValue(0)
                    ->integer(),
                Forms\Components\DateTimePicker::make('sale_ends_at')->label('İndirim bitişi')->native(false),
                Forms\Components\Toggle::make('is_published')->label('Yayında'),
                Forms\Components\DateTimePicker::make('published_at')->label('Yayın tarihi')->native(false),
            ])->columns(2),

            Forms\Components\Section::make('Dosyalar')->schema([
                Forms\Components\Repeater::make('files')
                    ->label('Oyun dosyaları')
                    ->relationship()
                    ->schema([
                        Forms\Components\TextInput::make('label')->label('Etiket')->required()->maxLength(255),
                        Forms\Components\Select::make('platform')
                            ->label('Platform')
                            ->options(collect(Platform::cases())->mapWithKeys(fn ($p) => [$p->value => $p->label()]))
                            ->required(),
 Forms\Components\TextInput::make('storage_path')
 ->label('Dosya yolu veya bağlantı')
 ->helperText('Diskteki yol ya da https bağlantısı. Drive, Mega ve doğrudan dosya adresi yazılabilir. .. içeremez.')
 ->required()
 ->maxLength(2048)
 ->rule(function () {
 return function (string $attribute, mixed $value, Closure $fail): void {
 $path = trim((string) $value);

 if (str_contains($path, '..')) {
 $fail('Yol .. içeremez.');

 return;
 }

 if (preg_match('#^https://#i', $path) === 1) {
 if (filter_var($path, FILTER_VALIDATE_URL) === false) {
 $fail('Bağlantı geçerli bir https adresi olmalı.');
 }

 return;
 }

 if (preg_match('#^[A-Za-z0-9_\-/.]+$#', $path) !== 1) {
 $fail('Disk yolu yalnızca harf, rakam, tire, alt çizgi, nokta ve / içerebilir.');
 }
 };
 }),
                        Forms\Components\TextInput::make('size_bytes')
 ->label('Boyut (MB)')
 ->helperText('100 yazarsan 100 MB olur.')
 ->numeric()
 ->minValue(0)
 ->required()
 ->formatStateUsing(fn ($state) => $state === null || $state === '' ? $state : (int) round(((int) $state) / 1048576))
 ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 1048576)),
                        Forms\Components\TextInput::make('version')->label('Sürüm')->maxLength(50),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('tags'))
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')->label('Kapak')->disk('public'),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('coin_price')->label('Coin')->sortable(),
                Tables\Columns\TextColumn::make('translation_percent')->label('Çeviri')->suffix('%')->sortable(),
                Tables\Columns\IconColumn::make('is_published')->label('Yayın')->boolean(),
                Tables\Columns\TextColumn::make('download_count')->label('İndirme')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('setCoinPrice')
                        ->label('Fiyat ata')
                        ->icon('heroicon-o-banknotes')
                        ->form([
                            Forms\Components\TextInput::make('coin_price')
                                ->label('Coin fiyatı')
                                ->helperText('0 ücretsiz yapar.')
                                ->numeric()
                                ->minValue(0)
                                ->integer()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            foreach ($records as $record) {
                                if (! $record instanceof Game) {
                                    continue;
                                }

                                $record->update(['coin_price' => (int) $data['coin_price']]);
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
