<?php

namespace App\Filament\Resources\SeriesResource\Pages;

use App\Filament\Resources\SeriesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeries extends CreateRecord
{
    protected static string $resource = SeriesResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SeriesResource::mutateGroupOnCreate($data);
    }
}
