<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChapterResource\Pages;
use App\Models\Chapter;
use App\Models\Series;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('series_id')
                ->label('Seri')
                ->options(fn () => Series::query()->orderBy('title')->pluck('title', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('number')->label('Numara')->numeric()->step(0.01)->required(),
            Forms\Components\TextInput::make('title')->label('Başlık')->maxLength(255),
            Forms\Components\DateTimePicker::make('published_at')->label('Yayın tarihi'),
            Forms\Components\TextInput::make('ruh_tasi_fiyat')->label('Ruh taşı fiyatı')->numeric()->minValue(0)->default(0)->required(),
            Forms\Components\Toggle::make('vip_ucretsiz')->label('VIP ücretsiz'),
            Forms\Components\DateTimePicker::make('erken_erisim_bitis')->label('Erken erişim bitiş'),
            Forms\Components\Checkbox::make('adult_characters_confirmed')
                ->label('Tüm karakterler yetişkindir; reşit olmayan karakter içeren içerik yasaktır')
                ->accepted()
                ->dehydrated(false)
                ->visible(fn (?Chapter $record) => $record === null)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('series.title')->label('Seri')->searchable(),
                Tables\Columns\TextColumn::make('number')->label('No')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Başlık'),
                Tables\Columns\TextColumn::make('ruh_tasi_fiyat')->label('Ruh taşı'),
                Tables\Columns\IconColumn::make('vip_ucretsiz')->label('VIP')->boolean(),
                Tables\Columns\TextColumn::make('published_at')->label('Yayın')->dateTime('d.m.Y H:i'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('series');
        $user = auth()->user();

        if ($user?->hasRole(User::ROLE_CEVIRI) && ! $user->hasRole(User::ROLE_ADMIN)) {
            $query->whereHas('series', fn (Builder $series) => $series->where('translator_group_id', $user->translator_group_id));
        }

        return $query;
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
