<?php

namespace App\Filament\Resources;

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Filament\Resources\SeriesResource\Pages;
use App\Filament\Resources\SeriesResource\RelationManagers\ChaptersRelationManager;
use App\Models\Series;
use App\Models\Tag;
use App\Models\TranslatorGroup;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class SeriesResource extends Resource
{
    protected static ?string $model = Series::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Seriler';

    protected static ?string $modelLabel = 'Seri';

    protected static ?string $pluralModelLabel = 'Seriler';

    protected static ?string $navigationGroup = 'İçerik';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('translator_group_id')
                ->label('Çeviri ekibi')
                ->options(fn () => TranslatorGroup::query()->orderBy('name')->pluck('name', 'id'))
                ->default(fn () => auth()->user()?->translator_group_id)
                ->disabled(fn () => auth()->user()?->hasRole(User::ROLE_CEVIRI) && ! auth()->user()?->hasRole(User::ROLE_ADMIN))
                ->dehydrated()
                ->required()
                ->searchable(),
            Forms\Components\TextInput::make('title')
                ->label('Başlık')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
            Forms\Components\TextInput::make('slug')
                ->label('Kısa ad')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TagsInput::make('alternative_names')
                ->label('Alternatif adlar')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('description')
                ->label('Açıklama')
                ->rows(4)
                ->columnSpanFull(),
            Forms\Components\FileUpload::make('cover_path')
                ->label('Kapak')
                ->image()
                ->disk(config('content.image_disk'))
                ->directory('covers')
                ->visibility('private'),
            Forms\Components\Select::make('type')
                ->label('Tür')
                ->options(collect(SeriesType::cases())->mapWithKeys(fn (SeriesType $type) => [$type->value => $type->label()]))
                ->required(),
            Forms\Components\Select::make('status')
                ->label('Durum')
                ->options(collect(SeriesStatus::cases())->mapWithKeys(fn (SeriesStatus $status) => [$status->value => $status->label()]))
                ->required(),
            Forms\Components\Select::make('content_level')
                ->label('İçerik seviyesi')
                ->options(collect(ContentLevel::cases())->mapWithKeys(fn (ContentLevel $level) => [$level->value => $level->label()]))
                ->required(),
            Forms\Components\TextInput::make('year')
                ->label('Yıl')
                ->numeric()
                ->minValue(1950)
                ->maxValue(2100),
            Forms\Components\TextInput::make('author')->label('Yazar')->maxLength(255),
            Forms\Components\TextInput::make('artist')->label('Çizer')->maxLength(255),
            Forms\Components\Select::make('tags')
                ->label('Etiketler')
                ->relationship('tags', 'name')
                ->multiple()
                ->preload()
                ->options(fn () => Tag::query()->orderBy('name')->pluck('name', 'id')),
            Forms\Components\Toggle::make('hidden')->label('Gizli'),
            Forms\Components\Checkbox::make('adult_characters_confirmed')
                ->label('Tüm karakterler yetişkindir; reşit olmayan karakter içeren içerik yasaktır')
                ->accepted()
                ->dehydrated(false)
                ->visible(fn (?Series $record) => $record === null)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlık')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('translatorGroup.name')->label('Ekip'),
                Tables\Columns\TextColumn::make('type')->label('Tür')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Durum')->badge(),
                Tables\Columns\TextColumn::make('content_level')->label('Seviye'),
                Tables\Columns\IconColumn::make('hidden')->label('Gizli')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('Tür')->options(collect(SeriesType::cases())->mapWithKeys(fn (SeriesType $type) => [$type->value => $type->label()])),
                Tables\Filters\SelectFilter::make('status')->label('Durum')->options(collect(SeriesStatus::cases())->mapWithKeys(fn (SeriesStatus $status) => [$status->value => $status->label()])),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('gizle')
                    ->label('Gizle')
                    ->icon('heroicon-o-eye-slash')
                    ->visible(fn (Series $record) => ! $record->hidden && auth()->user()?->hasRole(User::ROLE_ADMIN))
                    ->requiresConfirmation()
                    ->action(fn (Series $record) => $record->update(['hidden' => true])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
        $user = auth()->user();

        if ($user?->hasRole(User::ROLE_CEVIRI) && ! $user->hasRole(User::ROLE_ADMIN)) {
            $query->where('translator_group_id', $user->translator_group_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            ChaptersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeries::route('/'),
            'create' => Pages\CreateSeries::route('/create'),
            'edit' => Pages\EditSeries::route('/{record}/edit'),
        ];
    }

    public static function mutateGroupOnCreate(array $data): array
    {
        $user = auth()->user();

        if ($user?->hasRole(User::ROLE_CEVIRI) && ! $user->hasRole(User::ROLE_ADMIN)) {
            $data['translator_group_id'] = $user->translator_group_id;
        }

        return $data;
    }
}
