<?php

namespace App\Filament\Resources\TranslatorGroupResource\Pages;

use App\Filament\Resources\TranslatorGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTranslatorGroups extends ListRecords
{
    protected static string $resource = TranslatorGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
