<?php

namespace App\View\Components;

use App\Models\Game;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Component;

class Cover extends Component
{
    public string $src;

    public string $srcset;

    public function __construct(public Game $game)
    {
        $path = $game->cover_path;
        $fallback = asset('images/poster.svg');

        if (! is_string($path) || $path === '') {
            $this->src = $fallback;
            $this->srcset = '';

            return;
        }

        $base = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME);
        $disk = Storage::disk('public');
        $small = $base.'-240.webp';
        $large = $base.'-480.webp';

        $this->src = $disk->exists($small) ? asset('storage/'.$small) : ($disk->exists($path) ? asset('storage/'.$path) : $fallback);
        $this->srcset = collect([
            $small => '240w',
            $large => '480w',
        ])->filter(fn ($width, $file) => $disk->exists((string) $file))
            ->map(fn ($width, $file) => asset('storage/'.$file).' '.$width)
            ->implode(', ');
    }

    public function render(): View|Closure|string
    {
        return view('components.cover');
    }
}
