<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['uploaded_by'], $data['archive_disk'], $data['engine']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->forceFill([
            'uploaded_by' => auth()->id(),
            'archive_disk' => (string) config('membership.games_disk', 'local'),
            'engine' => 'renpy',
        ])->save();
    }
}
