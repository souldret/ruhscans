<?php

namespace App\Actions\Library;

use App\Models\LibraryEntry;
use App\Models\Series;
use App\Models\User;

class SaveLibraryEntry
{
    public function toggle(User $user, Series $series): bool
    {
        $existing = LibraryEntry::query()
            ->where('user_id', $user->id)
            ->where('series_id', $series->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        LibraryEntry::query()->create([
            'user_id' => $user->id,
            'series_id' => $series->id,
        ]);

        return true;
    }

    public function rate(User $user, Series $series, int $rating): LibraryEntry
    {
        return LibraryEntry::query()->updateOrCreate(
            ['user_id' => $user->id, 'series_id' => $series->id],
            ['rating' => $rating],
        );
    }
}
