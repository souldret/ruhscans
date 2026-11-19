<?php

namespace App\Filament\Resources\ChapterResource\Pages;

use App\Filament\Resources\ChapterResource;
use App\Filament\Resources\SeriesResource\RelationManagers\ChaptersRelationManager;
use App\Jobs\ProcessChapterZip;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditChapter extends EditRecord
{
    protected static string $resource = ChapterResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function afterSave(): void
    {
        $path = ChaptersRelationManager::uploadedPath($this->data['zip'] ?? null);

        if ($path === null) {
            return;
        }

        ProcessChapterZip::dispatch($this->record->id, 'local', $path, auth()->id());
    }
}
