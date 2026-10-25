<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function write(string $action, ?Model $subject = null, ?string $reason = null, array $meta = [], ?User $actor = null): AuditLog
    {
        $actor ??= auth()->user();

        return AuditLog::query()->create([
            'actor_id' => $actor instanceof User ? $actor->id : null,
            'action' => $action,
            'subject_type' => $subject instanceof Model ? $subject->getMorphClass() : null,
            'subject_id' => $subject instanceof Model ? $subject->getKey() : null,
            'reason' => $reason,
            'ip' => request()->ip(),
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
