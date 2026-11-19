<?php

namespace App\Filament\Resources\ChapterResource\Pages;

use App\Filament\Resources\ChapterResource;
use App\Filament\Resources\SeriesResource\RelationManagers\ChaptersRelationManager;
use App\Jobs\ProcessChapterZip;
use Filament\Resources\Pages\CreateRecord;

class CreateChapter extends CreateRecord
{
    protected static string $resource = ChapterResource::class;

    protected function afterCreate(): void
    {
        $path = ChaptersRelationManager::uploadedPath($this->data['zip'] ?? null);

        if ($path === null) {
            return;
        }

        ProcessChapterZip::dispatch($this->record->id, 'local', $path, auth()->id());
    }
}
