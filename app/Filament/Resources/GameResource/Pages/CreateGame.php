<?php

namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->draftAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function draftAction(): Action
    {
        return Action::make('draft')
            ->label('Taslak olarak kaydet')
            ->color('gray')
            ->action('saveDraft');
    }

    public function saveDraft(): void
    {
        $this->data['is_published'] = false;
        $this->data['published_at'] = null;
        $this->create();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['uploaded_by'], $data['archive_disk'], $data['engine']);

        if (empty($data['is_published'])) {
            $data['is_published'] = false;
            $data['published_at'] = null;
        } elseif (empty($data['published_at'])) {
            $data['published_at'] = now();
        }

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
