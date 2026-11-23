<?php

namespace App\Livewire;

use App\Enums\ContentLevel;
use App\Enums\SeriesStatus;
use App\Enums\SeriesType;
use App\Models\Tag;
use App\Services\Catalog\CatalogQuery;
use App\Services\Seo\MetaBuilder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportPageComponents\PageComponentConfig;
use Livewire\WithPagination;

class DiscoverCatalog extends Component
{
    use WithPagination;

    public int $perPage = 18;

    public bool $filtersOpen = false;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $level = '';

    #[Url(except: '')]
    public string $sort = 'yeni';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingLevel(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += 18;
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'type', 'status', 'level', 'sort']);
        $this->sort = 'yeni';
        $this->resetPage();
    }

    public function render(CatalogQuery $catalog, MetaBuilder $meta): View|PageComponentConfig
    {
        $series = $catalog->query([
            'q' => $this->q,
            'type' => $this->type !== '' ? $this->type : null,
            'status' => $this->status !== '' ? $this->status : null,
            'content_level' => $this->level !== '' ? $this->level : null,
            'sort' => $this->sort,
        ], auth()->user())->withCount('chapters')->paginate($this->perPage);

        return view('livewire.discover-catalog', [
            'series' => $series,
            'types' => SeriesType::cases(),
            'statuses' => SeriesStatus::cases(),
            'levels' => ContentLevel::cases(),
            'tags' => Tag::query()->orderBy('name')->limit(30)->get(),
            'blur' => auth()->user()?->kapak_blur ?? true,
        ])->layout('layouts.night', [
            'seo' => $meta->discover(),
        ]);
    }
}
