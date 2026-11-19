<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function __invoke(): View
    {
        $entries = LibraryEntry::query()
            ->where('user_id', auth()->id())
            ->with(['series.tags', 'lastChapter'])
            ->latest('updated_at')
            ->paginate(24);

        return view('library.index', [
            'entries' => $entries,
            'blur' => auth()->user()?->kapak_blur ?? true,
        ]);
    }
}
