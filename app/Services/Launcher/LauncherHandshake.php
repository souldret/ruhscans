<?php

namespace App\Services\Launcher;

use App\Models\Game;
use App\Models\LauncherDevice;
use App\Models\LauncherLoginLog;
use App\Models\User;
use Illuminate\Support\Carbon;

class LauncherHandshake
{
    public function __construct(private LauncherSigner $signer) {}

    /**
     * @param  array{game_slug: string, game_version: string, launcher_version: string, device_id: string, client_nonce: string}  $input
     * @return array{payload: string, signature: string, key_id: string}
     */
    public function issue(User $user, array $input, ?string $ip): array
    {
        $game = Game::query()->where('slug', $input['game_slug'])->first();
        $decision = $this->decide($user, $game, $input, $ip);

        if ($decision['allowed'] && $game instanceof Game) {
            $this->touchDevice($user, $game, $input, $ip);
        }

        LauncherLoginLog::query()->create([
            'user_id' => $user->id,
            'game_id' => $game?->id,
            'device_id' => $input['device_id'],
            'ip_hash' => $this->ipHash($ip),
            'result' => $decision['allowed'] ? 'allowed' : 'denied',
            'reason' => $decision['reason'],
        ]);

        $now = Carbon::now();
        $serverTime = $now->getTimestamp();
        $last = (int) config('launcher._last_server_time', 0);

        if ($serverTime <= $last) {
            $serverTime = $last + 1;
        }

        config(['launcher._last_server_time' => $serverTime]);

        $body = [
            'allowed' => $decision['allowed'],
            'reason' => $decision['reason'],
            'message' => $decision['message'],
            'min_launcher_version' => $decision['min_launcher_version'],
            'server_time' => $serverTime,
            'expires_at' => $serverTime + (int) config('launcher.payload_ttl_seconds', 120),
            'client_nonce' => $input['client_nonce'],
            'device_id' => $input['device_id'],
            'game_slug' => $input['game_slug'],
            'user_id' => $user->id,
            'removable_device_ids' => $decision['removable_device_ids'],
        ];

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR;

        return $this->signer->sign((string) json_encode($body, $flags));
    }

    /**
     * @param  array{game_slug: string, game_version: string, launcher_version: string, device_id: string, client_nonce: string}  $input
     * @return array{allowed: bool, reason: string, message: string, min_launcher_version: ?string, removable_device_ids: list<string>}
     */
    private function decide(User $user, ?Game $game, array $input, ?string $ip): array
    {
        $empty = [
            'allowed' => false,
            'reason' => 'denied',
            'message' => '',
            'min_launcher_version' => null,
            'removable_device_ids' => [],
        ];

        if ($this->banned($user)) {
 return [...$empty, 'reason' => 'banned', 'message' => 'Bu hesap kapatıldı. Giriş yapılamaz.'];
 }

        if (! $game instanceof Game || $game->trashed()) {
            return [...$empty, 'reason' => 'unknown_game', 'message' => 'Oyun bulunamadı.'];
        }

        $min = $game->launcher_min_version;

        if (! $game->launcher_enabled) {
            return [
                ...$empty,
                'reason' => 'kill_switch',
                'min_launcher_version' => $min,
                'message' => filled($game->launcher_message)
                    ? (string) $game->launcher_message
                    : 'Bu oyunun başlatıcısı geçici olarak kapatıldı. Lütfen güncel sürümü indirin.',
            ];
        }

        if (filled($min) && version_compare($input['launcher_version'], (string) $min, '<')) {
            return [
                ...$empty,
                'reason' => 'outdated',
                'min_launcher_version' => $min,
                'message' => filled($game->launcher_message)
                    ? (string) $game->launcher_message
                    : 'Başlatıcı sürümün eski. En az '.$min.' sürümüne güncelle.',
            ];
        }

        $limit = $this->deviceDecision($user, $game, $input['device_id']);

        if (! $limit['allowed']) {
            return [
                ...$empty,
                'reason' => 'device_limit',
                'min_launcher_version' => $min,
                'message' => $limit['message'],
                'removable_device_ids' => $limit['removable_device_ids'],
            ];
        }

        return [
            'allowed' => true,
            'reason' => 'ok',
            'message' => filled($game->launcher_message) ? (string) $game->launcher_message : 'Giriş onaylandı.',
            'min_launcher_version' => $min,
            'removable_device_ids' => [],
        ];
    }

    private function banned(User $user): bool
 {
 $attributes = $user->getAttributes();

 if (! array_key_exists('deleted_at', $attributes)) {
 return User::withTrashed()->whereKey($user->getKey())->whereNotNull('deleted_at')->exists();
 }

 return $attributes['deleted_at'] !== null;
 }

 /**
 * @return array{allowed: bool, message: string, removable_device_ids: list<string>}
 */
 private function deviceDecision(User $user, Game $game, string $deviceId): array
    {
        $window = now()->subDays((int) config('launcher.device_window_days', 30));
        $limit = (int) config('launcher.device_limit', 3);

        $active = LauncherDevice::query()
            ->where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->where('last_seen_at', '>=', $window)
            ->orderByDesc('last_seen_at')
            ->get();

        if ($active->contains(fn (LauncherDevice $row) => $row->device_id === $deviceId)) {
            return ['allowed' => true, 'message' => '', 'removable_device_ids' => []];
        }

        if ($active->count() < $limit) {
            return ['allowed' => true, 'message' => '', 'removable_device_ids' => []];
        }

        $grace = now()->subMinutes((int) config('launcher.reinstall_grace_minutes', 30));
        $recent = $active->filter(fn (LauncherDevice $row) => $row->first_seen_at !== null && $row->first_seen_at->greaterThanOrEqualTo($grace));

        $ids = $recent->pluck('device_id')->map(fn ($id) => (string) $id)->values()->all();

        if ($ids === []) {
            $ids = $active->pluck('device_id')->map(fn ($id) => (string) $id)->values()->all();
        }

        return [
            'allowed' => false,
            'message' => 'Bu hesapta bu oyun için cihaz limiti doldu (en fazla '.$limit.' cihaz, son '.(int) config('launcher.device_window_days', 30).' gün). Hesap sayfasından eski cihazı kaldır, sonra yeniden dene. Aynı oyunu yeniden kurduysan yeni cihazın kotayı doldurmasın diye eski kaydı silmen yeterli.',
            'removable_device_ids' => $ids,
        ];
    }

    /**
     * @param  array{game_slug: string, game_version: string, launcher_version: string, device_id: string, client_nonce: string}  $input
     */
    private function touchDevice(User $user, Game $game, array $input, ?string $ip): void
    {
        $now = now();
        $row = LauncherDevice::query()->firstOrNew([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'device_id' => $input['device_id'],
        ]);

        if (! $row->exists) {
            $row->first_seen_at = $now;
        }

        $row->last_seen_at = $now;
        $row->ip_hash = $this->ipHash($ip);
        $row->last_launcher_version = $input['launcher_version'];
        $row->save();
    }

    private function ipHash(?string $ip): ?string
    {
        if ($ip === null || $ip === '') {
            return null;
        }

        return hash('sha256', $ip.'|'.(string) config('app.key'));
    }
}
