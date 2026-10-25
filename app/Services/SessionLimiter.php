<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SessionLimiter
{
    public function enforce(User $user, string $currentId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        $limit = max(1, (int) config('membership.max_sessions', 2));

        $extra = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentId)
            ->orderByDesc('last_activity')
            ->skip($limit - 1)
            ->take(100)
            ->pluck('id');

        if ($extra->isEmpty()) {
            return;
        }

        DB::table('sessions')->whereIn('id', $extra->all())->delete();
    }
}
