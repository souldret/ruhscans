<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UserResource::stoneAction(),
            UserResource::vipAction(),
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('name')->label('Ad'),
            TextEntry::make('email')->label('E-posta'),
            TextEntry::make('roles.name')->label('Roller')->badge(),
            TextEntry::make('ruh_tasi_bakiye')->label('Bakiye'),
            TextEntry::make('vip_bitis')->label('VIP bitiş')->dateTime('d.m.Y H:i')->placeholder('Yok'),
            TextEntry::make('blocked_at')->label('Engel')->dateTime('d.m.Y H:i')->placeholder('Engelli değil'),
            TextEntry::make('email_verified_at')->label('E-posta doğrulama')->dateTime('d.m.Y H:i')->placeholder('Doğrulanmamış'),
        ])->columns(2);
    }
}
