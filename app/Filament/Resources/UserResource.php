<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use App\Services\CoinAdjuster;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Üye';

    protected static ?string $pluralModelLabel = 'Üyeler';

    protected static ?string $navigationLabel = 'Üyeler';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Ad')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->label('E-posta')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),
            Forms\Components\Select::make('role')
                ->label('Rol')
                ->options(collect(UserRole::cases())->mapWithKeys(
                    fn (UserRole $role) => [$role->value => $role->label()]
                ))
                ->required(),
            Forms\Components\TextInput::make('coin_balance')
                ->label('Coin bakiyesi')
                ->numeric()
                ->disabled()
                ->dehydrated(false),
            Forms\Components\FileUpload::make('avatar_path')
                ->label('Avatar')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->disk('public')
                ->directory('avatars')
                ->avatar(),
            Forms\Components\TextInput::make('password')
                ->label('Parola')
                ->password()
                ->revealable()
                ->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-posta')->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Rol')
                    ->formatStateUsing(fn (UserRole $state) => $state->label()),
                Tables\Columns\TextColumn::make('coin_balance')->label('Coin')->sortable(),
                Tables\Columns\IconColumn::make('coin_review')->label('İnceleme')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Kayıt')->dateTime('d.m.Y')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjustCoins')
                    ->label('Coin düzelt')
                    ->form([
                        Forms\Components\TextInput::make('amount')->label('Miktar')->numeric()->integer()->required(),
                        Forms\Components\Textarea::make('reason')->label('Sebep')->required()->maxLength(255),
                    ])
                    ->action(function (User $record, array $data, CoinAdjuster $adjuster): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        $adjuster->adjust($actor, $record, (int) $data['amount'], $data['reason']);
                        Notification::make()->title('Bakiye güncellendi')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
