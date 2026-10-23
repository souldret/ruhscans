<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __invoke(Game $game): StreamedResponse|RedirectResponse
    {
        $this->authorize('download', $game);

        if (! Storage::disk($game->archive_disk)->exists($game->archive_path)) {
            return back()->with('error', 'Oyun dosyası şu anda indirilemiyor. Lütfen daha sonra tekrar deneyin.');
        }

        Download::query()->create([
            'user_id' => auth()->id(),
            'game_id' => $game->id,
            'ip_address' => request()->ip(),
        ]);

        return Storage::disk($game->archive_disk)->download(
            $game->archive_path,
            $game->slug.'.zip',
        );
    }
}
