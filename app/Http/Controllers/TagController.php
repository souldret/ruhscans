<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\Catalog\CatalogQuery;
use App\Services\Seo\MetaBuilder;
use Illuminate\View\View;

class TagController extends Controller
{
    public function show(Tag $tag, CatalogQuery $catalog, MetaBuilder $meta): View
    {
        $series = $catalog->query(['tag' => $tag->id, 'sort' => 'yeni'], auth()->user())
            ->withCount('chapters')
            ->paginate(24);

        return view('tags.show', [
            'tag' => $tag,
            'series' => $series,
            'blur' => auth()->user()?->kapak_blur ?? true,
            'seo' => $meta->tag($tag),
        ]);
    }
}
