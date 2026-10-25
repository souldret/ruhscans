<?php

namespace App\Jobs;

use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class MakeCoverVariants implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $gameId) {}

    public function handle(): void
    {
        $game = Game::query()->find($this->gameId);

        if ($game === null || ! is_string($game->cover_path) || $game->cover_path === '') {
            return;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($game->cover_path)) {
            return;
        }

        $binary = $disk->get($game->cover_path);
        $source = is_string($binary) ? @imagecreatefromstring($binary) : false;

        if ($source === false) {
            return;
        }

        $base = pathinfo($game->cover_path, PATHINFO_DIRNAME).'/'.pathinfo($game->cover_path, PATHINFO_FILENAME);

        foreach ([240, 480] as $width) {
            $height = (int) round(imagesy($source) * ($width / max(1, imagesx($source))));
            $frame = imagescale($source, $width, $height);

            if ($frame === false) {
                continue;
            }

            foreach (['webp' => 'imagewebp', 'avif' => 'imageavif'] as $extension => $writer) {
                if (! function_exists($writer)) {
                    continue;
                }

                ob_start();
                $writer($frame, null, 70);
                $encoded = ob_get_clean();

                if (is_string($encoded) && $encoded !== '') {
                    $disk->put($base.'-'.$width.'.'.$extension, $encoded);
                }
            }

            imagedestroy($frame);
        }

        imagedestroy($source);
    }
}
