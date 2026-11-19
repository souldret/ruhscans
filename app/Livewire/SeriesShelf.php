<?php

namespace App\Livewire;

use App\Actions\Library\SaveLibraryEntry;
use App\Models\LibraryEntry;
use App\Models\Series;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class SeriesShelf extends Component
{
    public Series $series;

    public ?int $rating = null;

    public bool $saved = false;

    public function mount(Series $series): void
    {
        $this->series = $series;

        if (! auth()->check()) {
            return;
        }

        $entry = LibraryEntry::query()
            ->where('user_id', auth()->id())
            ->where('series_id', $series->id)
            ->first();

        $this->saved = $entry !== null;
        $this->rating = $entry?->rating;
    }

    public function toggle(SaveLibraryEntry $action): void
    {
        abort_unless(auth()->check(), 403);

        $this->saved = $action->toggle(auth()->user(), $this->series);
        $this->dispatch('toast', message: $this->saved ? 'Kütüphaneye eklendi.' : 'Kütüphaneden çıkarıldı.');
    }

    public function rate(int $value, SaveLibraryEntry $action): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless($value >= 1 && $value <= 5, 422);

        $entry = $action->rate(auth()->user(), $this->series, $value);
        $this->rating = $entry->rating;
        $this->saved = true;
        $this->dispatch('toast', message: 'Puanın kaydedildi.');
    }

    public function render(): View
    {
        return view('livewire.series-shelf');
    }
}
