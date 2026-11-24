<?php

namespace App\Services\Profile;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SessionManager
{
    public function record(User $user, string $sessionId, ?string $ip, ?string $userAgent): void
    {
        if ($sessionId === '') {
            return;
        }

        UserSession::query()->updateOrCreate(
            ['id' => $sessionId],
            [
                'user_id' => $user->id,
                'ip_address' => $ip ? Str::limit($ip, 45, '') : null,
                'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
                'last_activity' => now(),
            ],
        );
    }

    /**
     * @return Collection<int, UserSession>
     */
    public function listFor(User $user): Collection
    {
        return UserSession::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->limit(20)
            ->get();
    }

    public function logoutOtherDevices(User $user, string $currentSessionId): int
    {
        $deleted = UserSession::query()
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        }

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['revoked_count' => $deleted])
            ->log('Diğer cihazlardaki oturumlar kapatıldı.');

        return $deleted;
    }

    public function revokeOne(User $user, string $sessionId, string $currentSessionId): bool
    {
        if ($sessionId === $currentSessionId) {
            return false;
        }

        $deleted = UserSession::query()
            ->where('user_id', $user->id)
            ->where('id', $sessionId)
            ->delete();

        if ($deleted === 0) {
            return false;
        }

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', $sessionId)
                ->delete();
        }

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->log('Tekil cihaz oturumu kapatıldı.');

        return true;
    }
}
