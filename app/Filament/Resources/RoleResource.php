<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Filament\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Roller';

    protected static ?string $modelLabel = 'Rol';

    protected static ?string $pluralModelLabel = 'Roller';

    protected static ?string $navigationGroup = 'Kullanıcılar';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return AdminAccess::admin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Ad')->required()->maxLength(80)->unique(ignoreRecord: true),
            Forms\Components\Hidden::make('guard_name')->default('web'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Rol')->searchable(),
                Tables\Columns\TextColumn::make('users_count')->label('Üye')->counts('users'),
                Tables\Columns\TextColumn::make('guard_name')->label('Koruma'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
