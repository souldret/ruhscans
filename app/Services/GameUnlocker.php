<?php

namespace App\Services;

use App\Enums\CoinTransactionType;
use App\Mail\CoinReceiptMail;
use App\Models\Game;
use App\Models\GameUnlock;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Oyunu güncel fiyattan açar. Fiyat istemciden gelmez, satır kilitlenerek okunur.
 */
class GameUnlocker
{
    public function __construct(private readonly CoinService $coins) {}

    public function unlock(User $user, Game $game): GameUnlock
    {
        return DB::transaction(function () use ($user, $game): GameUnlock {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $existing = GameUnlock::query()
                ->where('user_id', $user->id)
                ->where('game_id', $game->id)
                ->first();

            if ($existing instanceof GameUnlock) {
                return $existing;
            }

            $price = Game::query()->whereKey($game->id)->lockForUpdate()->firstOrFail()->effectivePrice();

            try {
                $unlock = GameUnlock::query()->create([
                    'user_id' => $user->id,
                    'game_id' => $game->id,
                    'coins_paid' => $price,
                ]);
            } catch (UniqueConstraintViolationException) {
                return GameUnlock::query()
                    ->where('user_id', $user->id)
                    ->where('game_id', $game->id)
                    ->firstOrFail();
            }

            if ($price > 0) {
                $this->coins->debit(
                    $user,
                    $price,
                    CoinTransactionType::GameUnlock,
                    ModelRef::of($unlock),
                    $game->title,
                    'game-unlock:'.$user->id.':'.$game->id,
                );

                if (config('coins.receipts')) {
                    Mail::to($user)->send(new CoinReceiptMail(
                        $user,
                        'Oyun açıldı',
                        $game->title.' için '.$price.' coin harcandı.',
                        (($buyer = $user->fresh()) instanceof User ? $buyer->coin_balance : 0),
                    ));
                }
            }

            app(CoinAnomaly::class)->unlocks($user);

            return $unlock;
        });
    }

    public function refund(User $actor, GameUnlock $unlock, string $reason): void
    {
        DB::transaction(function () use ($actor, $unlock, $reason): void {
            $locked = GameUnlock::query()->whereKey($unlock->id)->lockForUpdate()->first();

            if (! $locked instanceof GameUnlock) {
                return;
            }

            if ($locked->coins_paid > 0) {
                $this->coins->credit(
                    User::query()->findOrFail($locked->user_id),
                    $locked->coins_paid,
                    CoinTransactionType::Refund,
                    ModelRef::of($locked),
                    $reason,
                    'game-refund:'.$locked->id,
                    $actor,
                );
            }

            $locked->delete();
            app(AuditLogger::class)->write('game.refunded', $locked, $reason, [
                'coins_paid' => $locked->coins_paid,
                'user_id' => $locked->user_id,
            ], $actor);
        });
    }
}
