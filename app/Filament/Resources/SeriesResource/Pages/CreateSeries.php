<?php

namespace App\Filament\Resources\SeriesResource\Pages;

use App\Filament\Resources\SeriesResource;
use App\Jobs\ProcessSeriesCover;
use Filament\Resources\Pages\CreateRecord;

class CreateSeries extends CreateRecord
{
    protected static string $resource = SeriesResource::class;

    protected function afterCreate(): void
    {
        $path = $this->record->cover_path;

        if (blank($path)) {
            return;
        }

        ProcessSeriesCover::dispatch(
            $this->record->id,
            (string) config('content.image_disk'),
            (string) $path,
        );
    }
}
