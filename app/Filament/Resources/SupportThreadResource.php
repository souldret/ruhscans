<?php

namespace App\Filament\Resources;

use App\Enums\SupportThreadStatus;
use App\Filament\Resources\SupportThreadResource\Pages;
use App\Models\SupportThread;
use App\Models\User;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupportThreadResource extends Resource
{
    protected static ?string $model = SupportThread::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Destek';

    protected static ?string $navigationLabel = 'Yazışmalar';

    protected static ?string $modelLabel = 'Yazışma';

    protected static ?string $pluralModelLabel = 'Yazışmalar';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = SupportThread::query()->where('unread_by_admin', true)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Konu')->schema([
                Infolists\Components\TextEntry::make('subject')->label('Konu'),
                Infolists\Components\TextEntry::make('user.name')->label('Üye'),
                Infolists\Components\TextEntry::make('user.email')->label('E-posta')->copyable(),
                Infolists\Components\TextEntry::make('status')
                    ->label('Durum')
                    ->formatStateUsing(fn (SupportThreadStatus $state) => $state->label()),
                Infolists\Components\TextEntry::make('created_at')->label('Açılış')->dateTime('d.m.Y H:i'),
            ])->columns(2),
            Infolists\Components\Section::make('Mesajlar')->schema([
                Infolists\Components\RepeatableEntry::make('messages')
                    ->label('')
                    ->schema([
                        Infolists\Components\TextEntry::make('author')
                            ->label('Yazan')
                            ->state(fn ($record) => $record->is_staff ? 'Yönetici' : ($record->user?->name ?? 'Üye')),
                        Infolists\Components\TextEntry::make('created_at')->label('Zaman')->dateTime('d.m.Y H:i'),
                        Infolists\Components\TextEntry::make('body')->label('Mesaj')->columnSpanFull(),
                    ])
                    ->columns(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('unread_by_admin')->label('Yeni')->boolean(),
                Tables\Columns\TextColumn::make('subject')->label('Konu')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('user.name')->label('Üye')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('E-posta')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Durum')
                    ->formatStateUsing(fn (SupportThreadStatus $state) => $state->label()),
                Tables\Columns\TextColumn::make('last_message_at')->label('Son mesaj')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Durum')
                    ->options(collect(SupportThreadStatus::cases())->mapWithKeys(
                        fn (SupportThreadStatus $status) => [$status->value => $status->label()]
                    )),
                Tables\Filters\TernaryFilter::make('unread_by_admin')->label('Yönetici okumadı'),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('reply')
                    ->label('Yanıtla')
                    ->icon('heroicon-o-paper-airplane')
                    ->form([
                        Forms\Components\Textarea::make('body')->label('Yanıt')->required()->minLength(2)->maxLength(4000),
                    ])
                    ->action(function (SupportThread $record, array $data): void {
                        $actor = auth()->user();
                        abort_unless($actor instanceof User, 403);
                        $record->post($actor, $data['body']);
                        Notification::make()->title('Yanıt gönderildi')->success()->send();
                    }),
                Tables\Actions\Action::make('close')
                    ->label('Kapat')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (SupportThread $record) => $record->status !== SupportThreadStatus::Closed)
                    ->action(function (SupportThread $record): void {
                        $record->close();
                        Notification::make()->title('Yazışma kapatıldı')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupportThreads::route('/'),
            'view' => Pages\ViewSupportThread::route('/{record}'),
        ];
    }
}
