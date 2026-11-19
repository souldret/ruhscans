<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use App\Models\Series;
use Illuminate\View\View;

class SeriesController extends Controller
{
    public function show(Series $series): View
    {
        abort_if($series->hidden, 404);

        $series->load([
            'tags',
            'translatorGroup',
            'chapters' => fn ($chapters) => $chapters->orderByDesc('number'),
        ]);

        $entry = auth()->check()
            ? LibraryEntry::query()
                ->where('user_id', auth()->id())
                ->where('series_id', $series->id)
                ->first()
            : null;

        return view('series.show', [
            'series' => $series,
            'entry' => $entry,
            'blur' => auth()->user()?->kapak_blur ?? true,
        ]);
    }
}
