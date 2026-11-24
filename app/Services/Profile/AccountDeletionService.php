<?php

namespace App\Services\Profile;

use App\Models\RedeemCode;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccountDeletionService
{
    public function __construct(
        private readonly AvatarService $avatars,
    ) {}

    /**
     * Kişisel verileri, oturumları, kütüphane ve bölüm açılışlarını siler;
     * mali denetim bütünlüğü için ledger (wallet_transactions) ve kullanılan kod kayıtlarını anonimleştirir.
     */
    public function handle(User $user): void
    {
        $userId = $user->id;
        $avatarPath = $user->avatar_path;

        DB::transaction(function () use ($user, $userId): void {
            WalletTransaction::query()
                ->where('user_id', $userId)
                ->update(['user_id' => null]);

            RedeemCode::query()
                ->where('kullanan_user_id', $userId)
                ->update(['kullanan_user_id' => null]);

            $user->trackedSessions()->delete();
            $user->libraryEntries()->delete();
            $user->chapterUnlocks()->delete();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $userId)->delete();
            }

            activity('profile')
                ->withProperties(['deleted_user_id' => $userId])
                ->log('Kullanıcı hesabı silindi ve cüzdan kayıtları anonimleştirildi.');

            $user->delete();
        });

        $this->avatars->deletePath($avatarPath);
    }
}
