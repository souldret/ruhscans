<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TranslatorGroupResource\Pages;
use App\Models\TranslatorGroup;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TranslatorGroupResource extends Resource
{
    protected static ?string $model = TranslatorGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Çeviri ekipleri';

    protected static ?string $modelLabel = 'Çeviri ekibi';

    protected static ?string $pluralModelLabel = 'Çeviri ekipleri';

    protected static ?string $navigationGroup = 'İçerik';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Ad')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', Str::slug((string) $state))),
            Forms\Components\TextInput::make('slug')->label('Kısa ad')->required()->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->label('Açıklama')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('series_count')->counts('series')->label('Seri'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTranslatorGroups::route('/'),
            'create' => Pages\CreateTranslatorGroup::route('/create'),
            'edit' => Pages\EditTranslatorGroup::route('/{record}/edit'),
        ];
    }
}
