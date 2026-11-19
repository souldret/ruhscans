<?php

namespace App\Filament\Resources\TranslatorGroupResource\Pages;

use App\Filament\Resources\TranslatorGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTranslatorGroup extends EditRecord
{
    protected static string $resource = TranslatorGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make(), Actions\RestoreAction::make()];
    }
}
