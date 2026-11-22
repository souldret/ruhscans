<?php

namespace App\Filament\Resources\SeriesResource\Pages;

use App\Filament\Resources\SeriesResource;
use App\Jobs\ProcessSeriesCover;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSeries extends EditRecord
{
    protected static string $resource = SeriesResource::class;

    private ?string $previousCover = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $this->previousCover = $this->record->cover_path;
    }

    protected function afterSave(): void
    {
        $path = $this->record->cover_path;

        if ($path === $this->previousCover) {
            return;
        }

        ProcessSeriesCover::dispatch(
            $this->record->id,
            blank($path) ? null : (string) config('content.image_disk'),
            blank($path) ? null : (string) $path,
            $this->previousCover,
        );
    }
}
