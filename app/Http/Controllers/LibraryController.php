<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use App\Services\Seo\MetaBuilder;
use Illuminate\View\View;

class LibraryController extends Controller
{
    public function __invoke(MetaBuilder $meta): View
    {
        $entries = LibraryEntry::query()
            ->where('user_id', auth()->id())
            ->with(['series.tags', 'lastChapter'])
            ->latest('updated_at')
            ->paginate(24);

        return view('library.index', [
            'entries' => $entries,
            'blur' => auth()->user()?->kapak_blur ?? true,
            'seo' => $meta->privatePage(
                'Kütüphane',
                'Kaydettiğin 18+ seriler. Bu sayfa yalnızca sana aittir ve dizine eklenmez.',
            ),
        ]);
    }
}
