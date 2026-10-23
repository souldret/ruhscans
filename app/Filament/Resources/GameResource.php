<?php

namespace App\Filament\Resources;

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
        return $form
            ->schema([
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
                Forms\Components\Textarea::make('description')
                    ->label('Açıklama')
                    ->rows(5)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('version')
                    ->label('Sürüm')
                    ->maxLength(50),
                Forms\Components\TextInput::make('archive_path')
                    ->label('Arşiv yolu')
                    ->helperText('games diskindeki dosya yolu, örneğin games/ornek.zip')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Yayın tarihi')
                    ->native(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Başlık')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label('Sürüm'),
                Tables\Columns\TextColumn::make('downloads_count')
                    ->label('İndirme')
                    ->counts('downloads')
                    ->sortable(),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Yayın')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
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
