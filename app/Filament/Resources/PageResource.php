<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static bool $shouldRegisterNavigation = false;

 protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Sayfalar';

    protected static ?string $modelLabel = 'Sayfa';

    protected static ?string $pluralModelLabel = 'Sayfalar';

    protected static ?string $navigationGroup = 'İçerik';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('chapter_id')
                ->label('Bölüm')
                ->relationship('chapter', 'number')
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('order')->label('Sıra')->numeric()->required(),
            Forms\Components\TextInput::make('r2_path')->label('R2 yolu')->required()->maxLength(255),
            Forms\Components\TextInput::make('width')->label('En')->numeric(),
            Forms\Components\TextInput::make('height')->label('Boy')->numeric(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('chapter.series.title')->label('Seri'),
                Tables\Columns\TextColumn::make('chapter.number')->label('Bölüm'),
                Tables\Columns\TextColumn::make('order')->label('Sıra')->sortable(),
                Tables\Columns\TextColumn::make('width')->label('En'),
                Tables\Columns\TextColumn::make('height')->label('Boy'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('chapter.series');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
