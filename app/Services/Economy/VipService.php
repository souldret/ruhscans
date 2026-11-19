<?php

namespace App\Services\Economy;

use App\Models\User;
use Illuminate\Support\Carbon;

class VipService
{
    public function extend(User $user, int $days): Carbon
    {
        if ($days < 1) {
            throw new \InvalidArgumentException('VIP gün sayısı en az 1 olmalı.');
        }

        $start = $user->vip_bitis !== null && $user->vip_bitis->isFuture()
            ? $user->vip_bitis->copy()
            : now();

        $until = $start->addDays($days);
        $user->forceFill(['vip_bitis' => $until])->save();

        return $until;
    }
}
