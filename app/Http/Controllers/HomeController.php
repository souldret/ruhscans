<?php

namespace App\Http\Controllers;

use App\Models\LibraryEntry;
use App\Services\Catalog\CatalogCache;
use App\Services\Catalog\CatalogQuery;
use App\Services\Seo\MetaBuilder;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(CatalogCache $cache, CatalogQuery $catalog, MetaBuilder $meta): View
    {
        $viewer = auth()->user();
        $home = $cache->home();

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
            'showcase' => $catalog->visible($home['showcase'], $viewer),
            'trending' => $catalog->visible($home['trending'], $viewer),
            'fresh' => $catalog->visibleChapters($home['fresh'], $viewer),
            'tags' => $home['tags'],
            'continue' => $continue,
            'blur' => $viewer?->kapak_blur ?? true,
            'seo' => $meta->home(),
        ]);
    }
}
