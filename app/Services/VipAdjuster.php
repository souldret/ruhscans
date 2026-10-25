<?php

namespace App\Services;

use App\Enums\VipCodeStatus;
use App\Models\User;
use App\Models\VipCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class VipAdjuster
{
    public function __construct(private AuditLogger $audit) {}

    public function grant(User $actor, User $member, int $days, string $reason): Carbon
    {
        return DB::transaction(function () use ($actor, $member, $days, $reason) {
            $locked = User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            $current = $locked->vip_until;
            $start = $current instanceof Carbon && $current->isFuture() ? $current->copy() : now();
            $until = $start->copy()->addDays($days);

            $locked->forceFill(['vip_until' => $until])->save();

            $this->audit->write('vip.granted', $locked, $reason, [
                'days' => $days,
                'until' => $until->toIso8601String(),
            ], $actor);

            return $until;
        });
    }

    public function revokeDays(User $actor, User $member, int $days, string $reason): void
    {
        DB::transaction(function () use ($actor, $member, $days, $reason) {
            $locked = User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();
            $current = $locked->vip_until;
            $until = $current instanceof Carbon ? $current->copy()->subDays($days) : now();

            if ($until->lessThanOrEqualTo(now())) {
                $until = null;
            }

            $locked->forceFill(['vip_until' => $until])->save();

            $this->audit->write('vip.revoked', $locked, $reason, [
                'days' => $days,
                'until' => $until instanceof Carbon ? $until->toIso8601String() : null,
            ], $actor);
        });
    }

    public function clawback(User $actor, VipCode $code, string $reason): void
    {
        DB::transaction(function () use ($actor, $code, $reason) {
            $locked = VipCode::query()->whereKey($code->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== VipCodeStatus::Used || $locked->used_by === null) {
                return;
            }

            $member = User::query()->whereKey($locked->used_by)->lockForUpdate()->first();

            if ($member instanceof User) {
                $current = $member->vip_until;
                $until = $current instanceof Carbon
                    ? $current->copy()->subDays($locked->duration_days)
                    : null;

                if (! $until instanceof Carbon || $until->lessThanOrEqualTo(now())) {
                    $until = null;
                }

                $member->forceFill(['vip_until' => $until])->save();
            }

            $locked->forceFill(['status' => VipCodeStatus::Disabled])->save();

            $this->audit->write('code.clawback', $locked, $reason, [
                'user_id' => $locked->used_by,
                'days' => $locked->duration_days,
            ], $actor);
        });
    }
}
