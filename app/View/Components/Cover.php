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

    /** @var array<string, bool> */
    private static array $exists = [];

    public function __construct(
        public Game $game,
        public int $width = 120,
        public int $height = 160,
        public string $sizes = '(min-width: 640px) 160px, 144px',
        public bool $lazy = true,
    ) {
        $path = $game->cover_path;
        $fallback = asset('images/poster.svg');

        if (! is_string($path) || $path === '') {
            $this->src = $fallback;
            $this->srcset = '';

            return;
        }

        $base = pathinfo($path, PATHINFO_DIRNAME).'/'.pathinfo($path, PATHINFO_FILENAME);
        $small = $base.'-240.webp';
        $large = $base.'-480.webp';
        $smallExists = self::onDisk($small);
        $originalExists = $smallExists || self::onDisk($path);

        $this->src = $smallExists
            ? asset('storage/'.$small)
            : ($originalExists ? asset('storage/'.$path) : $fallback);
        $this->srcset = collect([
            $small => '240w',
            $large => '480w',
        ])->filter(fn ($width, $file) => self::onDisk((string) $file))
            ->map(fn ($width, $file) => asset('storage/'.$file).' '.$width)
            ->implode(', ');
    }

    private static function onDisk(string $path): bool
    {
        if (array_key_exists($path, self::$exists)) {
            return self::$exists[$path];
        }

        return self::$exists[$path] = Storage::disk('public')->exists($path);
    }

    public function render(): View|Closure|string
    {
        return view('components.cover');
    }
}
