<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\ChapterUnlocksRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\RedeemedCodesRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\WalletTransactionsRelationManager;
use App\Filament\Support\AdminAccess;
use App\Models\User;
use App\Services\Admin\MemberAdmin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Üyeler';

    protected static ?string $modelLabel = 'Üye';

    protected static ?string $pluralModelLabel = 'Üyeler';

    protected static ?string $navigationGroup = 'Kullanıcılar';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return AdminAccess::admin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('Ad')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->label('E-posta')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('roles')
                ->label('Roller')
                ->multiple()
                ->relationship('roles', 'name')
                ->options(fn () => Role::query()->orderBy('name')->pluck('name', 'id'))
                ->preload(),
            Forms\Components\Select::make('translator_group_id')
                ->label('Çeviri grubu')
                ->relationship('translatorGroup', 'name')
                ->searchable(),
            Forms\Components\DateTimePicker::make('vip_bitis')->label('VIP bitiş')->disabled()->dehydrated(false),
            Forms\Components\TextInput::make('ruh_tasi_bakiye')->label('Ruh taşı')->disabled()->dehydrated(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('E-posta')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->label('Rol')->badge(),
                Tables\Columns\TextColumn::make('ruh_tasi_bakiye')->label('Bakiye')->sortable(),
                Tables\Columns\TextColumn::make('vip_bitis')->label('VIP bitiş')->dateTime('d.m.Y H:i'),
                Tables\Columns\IconColumn::make('blocked_at')->label('Engelli')->boolean()->getStateUsing(fn (User $record) => $record->isBlocked()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Rol')
                    ->relationship('roles', 'name'),
                Tables\Filters\TernaryFilter::make('vip')
                    ->label('VIP')
                    ->queries(
                        true: fn (Builder $query) => $query->where('vip_bitis', '>', now()),
                        false: fn (Builder $query) => $query->where(fn (Builder $query) => $query->whereNull('vip_bitis')->orWhere('vip_bitis', '<=', now())),
                    ),
                Tables\Filters\TernaryFilter::make('blocked_at')
                    ->label('Engelli')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                self::stoneAction(),
                self::vipAction(),
                Tables\Actions\Action::make('engelle')
                    ->label(fn (User $record) => $record->isBlocked() ? 'Engeli kaldır' : 'Engelle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->forceFill(['blocked_at' => $record->isBlocked() ? null : now()])->save();
                        activity()->causedBy(auth()->user())->performedOn($record)->log($record->isBlocked() ? 'Hesap engellendi' : 'Hesap engeli kalktı');
                    }),
                Tables\Actions\Action::make('dogrula')
                    ->label('E-postayı doğrula')
                    ->visible(fn (User $record) => $record->email_verified_at === null)
                    ->action(function (User $record) {
                        $record->forceFill(['email_verified_at' => now()])->save();
                        activity()->causedBy(auth()->user())->performedOn($record)->log('E-posta elle doğrulandı');
                    }),
            ]);
    }

    public static function stoneAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('tas')
            ->label('Ruh taşı')
            ->icon('heroicon-o-sparkles')
            ->form([
                Forms\Components\Select::make('yon')->label('İşlem')->options(['ekle' => 'Ekle', 'dus' => 'Düş'])->required(),
                Forms\Components\TextInput::make('miktar')->label('Miktar')->numeric()->minValue(1)->required(),
                Forms\Components\Textarea::make('gerekce')->label('Gerekçe')->required()->maxLength(300),
            ])
            ->action(function (User $record, array $data, MemberAdmin $admin) {
                try {
                    $admin->adjustStones($record, auth()->user(), (int) $data['miktar'], $data['yon'] === 'ekle', $data['gerekce']);
                } catch (\Throwable $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('Bakiye güncellendi')->success()->send();
            });
    }

    public static function vipAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('vip')
            ->label('VIP gün')
            ->icon('heroicon-o-star')
            ->form([
                Forms\Components\Select::make('yon')->label('İşlem')->options(['ekle' => 'Ekle', 'kaldir' => 'Kaldır'])->required(),
                Forms\Components\TextInput::make('gun')->label('Gün')->numeric()->minValue(1)->required(),
                Forms\Components\Textarea::make('gerekce')->label('Gerekçe')->required()->maxLength(300),
            ])
            ->action(function (User $record, array $data, MemberAdmin $admin) {
                try {
                    $admin->adjustVip($record, auth()->user(), (int) $data['gun'], $data['yon'] === 'ekle', $data['gerekce']);
                } catch (\Throwable $exception) {
                    Notification::make()->title($exception->getMessage())->danger()->send();

                    return;
                }

                Notification::make()->title('VIP güncellendi')->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            WalletTransactionsRelationManager::class,
            ChapterUnlocksRelationManager::class,
            RedeemedCodesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
