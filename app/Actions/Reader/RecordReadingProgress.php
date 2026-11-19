<?php

namespace App\Actions\Reader;

use App\Models\Chapter;
use App\Models\LibraryEntry;
use App\Models\User;

class RecordReadingProgress
{
    public function handle(User $user, Chapter $chapter, int $pageOrder, int $pageCount): LibraryEntry
    {
        $pageOrder = max(1, $pageOrder);
        $completed = $pageCount > 0 && $pageOrder >= $pageCount;

        return LibraryEntry::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'series_id' => $chapter->series_id,
            ],
            [
                'last_chapter_id' => $chapter->id,
                'last_page_order' => $pageOrder,
                'completed' => $completed,
                'last_read_at' => now(),
            ],
        );
    }
}
