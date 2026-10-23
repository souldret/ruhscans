<?php

namespace App\Filament\Resources;

use App\Enums\GameAccess;
use App\Enums\Platform;
use App\Enums\TranslationStatus;
use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
                Forms\Components\TextInput::make('slug')
                    ->label('Adres')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('original_title')->label('Özgün ad')->maxLength(255),
                Forms\Components\TextInput::make('developer')->label('Geliştirici')->maxLength(255),
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
                    ->image()
                    ->disk('public')
                    ->directory('games/covers'),
                Forms\Components\FileUpload::make('banner_path')
                    ->label('Banner')
                    ->image()
                    ->disk('public')
                    ->directory('games/banners'),
                Forms\Components\FileUpload::make('screenshots')
                    ->label('Ekran görüntüleri')
                    ->image()
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
                Forms\Components\TextInput::make('size_mb')->label('Boyut (MB)')->numeric()->minValue(0)->required(),
                Forms\Components\Select::make('access')
                    ->label('Erişim')
                    ->options(collect(GameAccess::cases())->mapWithKeys(fn ($a) => [$a->value => $a->label()]))
                    ->required(),
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
                        Forms\Components\TextInput::make('storage_path')->label('Depolama yolu')->required()->maxLength(255),
                        Forms\Components\TextInput::make('size_bytes')->label('Boyut (bayt)')->numeric()->minValue(0)->required(),
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
            ->columns([
                Tables\Columns\ImageColumn::make('cover_path')->label('Kapak')->disk('public'),
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('access')->label('Erişim')->formatStateUsing(fn (GameAccess $state) => $state->label()),
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
