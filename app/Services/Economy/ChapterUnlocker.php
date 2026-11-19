<?php

namespace App\Services\Economy;

use App\Exceptions\Economy\InsufficientBalanceException;
use App\Models\Chapter;
use App\Models\ChapterUnlock;
use App\Models\User;
use App\Services\Reader\ChapterAccess;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ChapterUnlocker
{
    public function __construct(
        private readonly WalletService $wallet,
        private readonly ChapterAccess $access,
    ) {}

    public function unlock(User $user, Chapter $chapter): ChapterUnlock
    {
        return DB::transaction(function () use ($user, $chapter) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $lockedChapter = Chapter::query()->whereKey($chapter->id)->firstOrFail();

            $existing = ChapterUnlock::query()
                ->where('user_id', $lockedUser->id)
                ->where('chapter_id', $lockedChapter->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            if (! $this->access->isLocked($lockedChapter, $lockedUser)) {
                throw new \RuntimeException('Bölüm zaten açık.');
            }

            if ($this->access->reason($lockedChapter, $lockedUser) !== 'price') {
                throw new \RuntimeException('Bu bölüm ruh taşıyla açılamaz.');
            }

            $price = (int) $lockedChapter->ruh_tasi_fiyat;

            if ($price < 1) {
                throw new \RuntimeException('Bu bölüm ruh taşıyla açılamaz.');
            }

            if ((int) $lockedUser->ruh_tasi_bakiye < $price) {
                throw new InsufficientBalanceException('Ruh taşı bakiyesi yetersiz.');
            }

            try {
                $unlock = ChapterUnlock::query()->create([
                    'user_id' => $lockedUser->id,
                    'chapter_id' => $lockedChapter->id,
                ]);
            } catch (QueryException $exception) {
                $winner = ChapterUnlock::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('chapter_id', $lockedChapter->id)
                    ->first();

                if ($winner) {
                    return $winner;
                }

                throw $exception;
            }

            $this->wallet->debit(
                $lockedUser,
                $price,
                'bolum_acma',
                'unlock:'.$lockedUser->id.':'.$lockedChapter->id,
                Chapter::class,
                $lockedChapter->id,
            );

            $user->ruh_tasi_bakiye = $lockedUser->fresh()->ruh_tasi_bakiye;

            return $unlock;
        });
    }
}
