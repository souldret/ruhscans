<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use App\Services\Catalog\HomeCatalog;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(HomeCatalog $catalog): View
    {
        $viewer = auth()->user();

        $continue = $viewer
            ? LibraryEntry::query()
                ->where('user_id', $viewer->id)
                ->whereNotNull('last_chapter_id')
                ->with(['series.tags', 'lastChapter'])
                ->latest('last_read_at')
                ->limit(12)
                ->get()
            : collect();

        return view('home', [
            'showcase' => $catalog->showcase($viewer),
            'trending' => $catalog->trending($viewer),
            'fresh' => $catalog->freshChapters($viewer),
            'tags' => $catalog->tagCloud(),
            'continue' => $continue,
            'blur' => $viewer?->kapak_blur ?? true,
        ]);
    }
}
