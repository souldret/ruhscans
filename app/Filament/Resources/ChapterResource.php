<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChapterResource\Pages;
use App\Filament\Resources\ChapterResource\RelationManagers\PagesRelationManager;
use App\Filament\Resources\SeriesResource\RelationManagers\ChaptersRelationManager;
use App\Models\Chapter;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ChapterResource extends Resource
{
    protected static ?string $model = Chapter::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Bölümler';

    protected static ?string $modelLabel = 'Bölüm';

    protected static ?string $pluralModelLabel = 'Bölümler';

    protected static ?string $navigationGroup = 'İçerik';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema(ChaptersRelationManager::fields(withSeries: true))->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('series.title')->label('Seri')->searchable(),
                ...ChaptersRelationManager::columns(),
                \Filament\Tables\Columns\TextColumn::make('published_at')->label('Yayın')->dateTime('d.m.Y H:i'),
            ])
            ->actions(ChaptersRelationManager::rowActions(function (Chapter $record) {
                // ZIP, EditChapter form durumundan kuyruğa alınır.
            }))
            ->bulkActions([ChaptersRelationManager::bulk()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['series', 'latestUpload']);
    }

    public static function getRelations(): array
    {
        return [
            PagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChapters::route('/'),
            'create' => Pages\CreateChapter::route('/create'),
            'edit' => Pages\EditChapter::route('/{record}/edit'),
        ];
    }
}
