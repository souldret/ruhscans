<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\User;
use App\Services\DownloadAnomaly;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class DownloadController extends Controller
{
    public function __invoke(GameFile $file): RedirectResponse|Response
    {
        $game = $file->game;
        abort_unless($game instanceof Game && $game->is_published, 404);

        $user = auth()->user();
        $user = $user instanceof User ? $user : null;
        $locked = ! $game->isFree();
        $mustLogin = $locked || config('membership.require_login_for_free', true);

        if ($mustLogin && $user === null) {
            return redirect()->guest(route('login'));
        }

        if ($locked && ! ($user instanceof User && ($user->isAdmin() || $user->unlocks()->where('game_id', $game->id)->exists()))) {
            abort(response()->view('downloads.vip', [], 403));
        }

        if ($user instanceof User) {
            $limit = $user->isAdmin()
                ? (int) config('membership.download_limits.vip')
                : (int) config('membership.download_limits.member');

            $used = Download::query()
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->startOfDay())
                ->count();

            if ($used >= $limit) {
                abort(response()->view('downloads.limit', ['limit' => $limit], 429));
            }
        }

        DB::transaction(function () use ($user, $file, $game): void {
            if (! $user instanceof User) {
                return;
            }

            $today = now()->toDateString();
            $exists = Download::query()
                ->where('user_id', $user->id)
                ->where('game_file_id', $file->id)
                ->whereDate('day', $today)
                ->lockForUpdate()
                ->exists();

            if ($exists) {
                return;
            }

            Download::query()->create([
                'user_id' => $user->id,
                'game_file_id' => $file->id,
                'ip' => request()->ip(),
                'country' => request()->header('CF-IPCountry'),
                'day' => $today,
            ]);
            // Sayaç artışı updated_at'i değiştirmemeli; aksi halde oyun "güncellendi" görünür.
            Game::query()->whereKey($game->id)->toBase()->increment('download_count');
            app(DownloadAnomaly::class)->inspect($user, request()->header('CF-IPCountry'));
        });

        $minutes = (int) config('membership.signed_url_minutes', 5);
        $parameters = ['file' => $file];

        if ($user instanceof User) {
            $parameters['uye'] = $user->id;
        }

        if (config('membership.bind_download_ip')) {
            $parameters['ip'] = hash('sha256', (string) request()->ip());
        }

        return redirect()->away(URL::temporarySignedRoute(
            'downloads.signed',
            now()->addMinutes($minutes),
            $parameters,
        ));
    }
}
