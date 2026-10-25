<?php

namespace App\Filament\Resources\CoinPackageResource\Pages;

use App\Filament\Resources\CoinPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoinPackages extends ListRecords
{
    protected static string $resource = CoinPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
