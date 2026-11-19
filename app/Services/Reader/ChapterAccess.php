<?php

namespace App\Services\Reader;

use App\Models\Chapter;
use App\Models\ChapterUnlock;
use App\Models\User;

class ChapterAccess
{
    public function isLocked(Chapter $chapter, ?User $user): bool
    {
        return $this->reason($chapter, $user) !== null;
    }

    public function reason(Chapter $chapter, ?User $user): ?string
    {
        if ($chapter->published_at === null || $chapter->published_at->isFuture()) {
            return 'unpublished';
        }

        $early = $chapter->erken_erisim_bitis !== null && $chapter->erken_erisim_bitis->isFuture();
 $earlyEnded = $chapter->erken_erisim_bitis !== null && ! $chapter->erken_erisim_bitis->isFuture();
 $priced = (int) $chapter->ruh_tasi_fiyat > 0;

 if ($earlyEnded || (! $early && ! $priced)) {
 return null;
 }

        if ($user?->isVip() && ($chapter->vip_ucretsiz || $early)) {
            return null;
        }

        if ($user && $priced && $this->hasUnlock($user, $chapter) && ! $early) {
            return null;
        }

        if ($early) {
            return 'early';
        }

        return 'price';
    }

    public function hasUnlock(?User $user, Chapter $chapter): bool
    {
        if ($user === null) {
            return false;
        }

        return ChapterUnlock::query()
            ->where('user_id', $user->id)
            ->where('chapter_id', $chapter->id)
            ->exists();
    }

    /**
     * @return array{locked: bool, reason: string|null, price: int, vip_free: bool, early: bool, vip: bool}
     */
    public function gate(Chapter $chapter, ?User $user): array
    {
        $reason = $this->reason($chapter, $user);

        return [
            'locked' => $reason !== null,
            'reason' => $reason,
            'price' => (int) $chapter->ruh_tasi_fiyat,
            'vip_free' => (bool) $chapter->vip_ucretsiz,
            'early' => $chapter->erken_erisim_bitis !== null && $chapter->erken_erisim_bitis->isFuture(),
            'vip' => (bool) $user?->isVip(),
        ];
    }
}
