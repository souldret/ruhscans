<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SignedDownloadController extends Controller
{
    public function __invoke(Request $request, GameFile $file): StreamedResponse
    {
        $game = $file->game;
        abort_unless($game instanceof Game && $game->is_published, 403);

        if (config('membership.bind_download_ip')) {
            $bound = $request->query('ip');
            abort_unless(is_string($bound) && hash_equals($bound, hash('sha256', (string) $request->ip())), 403);
        }

        $userId = $request->query('uye');
        $locked = ! $game->isFree();

        // Kilitli dosya her zaman bir üyeye bağlı imzalı bağlantıyla indirilebilir.
        abort_if($locked && ! is_numeric($userId), 403);

        if (is_numeric($userId)) {
            $user = User::query()->find((int) $userId);
            abort_unless($user instanceof User, 403);

            abort_unless(! $locked || $user->isAdmin() || $user->unlocks()->where('game_id', $game->id)->exists(), 403);
        }

        $path = $file->storage_path;
        abort_unless($path !== '' && ! str_contains($path, '..'), 404);

        $diskName = (string) config('membership.games_disk', 'local');

        return Storage::disk($diskName)->download($path, basename($path));
    }
}
