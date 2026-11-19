<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\Economy\VipService;
use App\Services\Economy\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MemberAdmin
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly VipService $vip,
    ) {}

    public function adjustStones(User $member, User $actor, int $amount, bool $credit, string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException('Gerekçe zorunlu.');
        }

        $key = 'admin:'.$actor->id.':'.$member->id.':'.Str::uuid();

        DB::transaction(function () use ($member, $actor, $amount, $credit, $reason, $key) {
            if ($credit) {
                $this->wallet->credit($member, $amount, 'admin_ekle: '.$reason, $key, User::class, $actor->id);
            } else {
                $this->wallet->debit($member, $amount, 'admin_dus: '.$reason, $key, User::class, $actor->id);
            }

            activity()
                ->causedBy($actor)
                ->performedOn($member)
                ->withProperties([
                    'miktar' => $amount,
                    'yon' => $credit ? 'kredi' : 'borc',
                    'gerekce' => $reason,
                ])
                ->log($credit ? 'Ruh taşı eklendi' : 'Ruh taşı düşüldü');
        });
    }

    public function adjustVip(User $member, User $actor, int $days, bool $add, string $reason): void
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new \InvalidArgumentException('Gerekçe zorunlu.');
        }

        if ($add) {
            $until = $this->vip->extend($member, $days);
        } else {
            $until = $this->vip->shorten($member, $days);
        }

        activity()
            ->causedBy($actor)
            ->performedOn($member)
            ->withProperties([
                'gun' => $days,
                'yon' => $add ? 'ekle' : 'kaldir',
                'vip_bitis' => $until?->toIso8601String(),
                'gerekce' => $reason,
            ])
            ->log($add ? 'VIP gün eklendi' : 'VIP gün kaldırıldı');
    }
}
