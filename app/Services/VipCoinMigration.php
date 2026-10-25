<?php

namespace App\Services;

use App\Enums\CoinCodeStatus;
use App\Enums\CoinTransactionType;
use App\Enums\UserRole;
use App\Enums\VipCodeStatus;
use App\Models\CoinCode;
use App\Models\CoinCodeBatch;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\VipCode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * VIP kodlarını ve süren üyelikleri coin defterine taşır.
 * İkinci kez çalışınca aynı kaydı tekrar işlemez.
 */
class VipCoinMigration
{
    public function __construct(private readonly CoinService $coins) {}

    public function rate(): int
    {
        return max(0, (int) config('coins.vip_day_to_coin'));
    }

    public function coinsForDays(int $days): int
    {
        return $days * $this->rate();
    }

    /**
     * Canlı üye yoksa üyelik taşıması anlamsızdır. Yalnızca gerçek üyeler
     * sayılır; yönetici hesapları ve tohum verisi bu kararı etkilemez.
     */
    public function hasLiveMembers(): bool
    {
        return User::query()->where('role', UserRole::Member)->exists();
    }

    /**
     * @return array{codes: list<array<string, mixed>>, members: list<array<string, mixed>>, games: list<array<string, mixed>>}
     */
    public function plan(): array
    {
        return [
            'codes' => $this->codePlan(),
            'members' => $this->hasLiveMembers() ? $this->memberPlan() : [],
            'games' => $this->gamePlan(),
        ];
    }

    /**
     * @return array{codes: int, members: int, coins: int, games: int}
     */
    public function apply(): array
    {
        $applied = ['codes' => 0, 'members' => 0, 'coins' => 0, 'games' => 0];

        DB::transaction(function () use (&$applied): void {
            $applied['codes'] = $this->convertCodes();

            if ($this->hasLiveMembers()) {
                [$members, $coins] = $this->grantMembers();
                $applied['members'] = $members;
                $applied['coins'] = $coins;
            }

            $applied['games'] = $this->priceGames();
        });

        return $applied;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function codePlan(): array
    {
        $taken = CoinCode::query()->pluck('code')->all();

        return array_values(VipCode::query()
            ->where('status', VipCodeStatus::Unused)
            ->orderBy('id')
            ->get()
            ->reject(fn (VipCode $code) => in_array($code->code, $taken, true))
            ->map(fn (VipCode $code) => [
                'code' => $code->code,
                'days' => $code->duration_days,
                'coins' => $this->coinsForDays($code->duration_days),
            ])
            ->all());
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function memberPlan(): array
    {
        return array_values(User::query()
            ->where('role', UserRole::Member)
            ->whereNotNull('vip_until')
            ->where('vip_until', '>', now())
            ->orderBy('id')
            ->get()
            ->reject(fn (User $user) => $this->alreadyGranted($user))
            ->map(function (User $user) {
                $days = $this->remainingDays($user);

                return [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'days' => $days,
                    'coins' => $this->coinsForDays($days),
                ];
            })
            ->filter(fn (array $row) => $row['coins'] > 0)
            ->all());
    }

    /**
     * Eski VIP oyunlarına varsayılan fiyatı yazar. access sütunu coin
     * geçişinde kalktığı için yeni kurulumda yapılacak iş yoktur ve
     * ücretsiz oyunlar olduğu gibi kalır.
     *
     * @return list<array<string, mixed>>
     */
    private function gamePlan(): array
    {
        if (! DB::getSchemaBuilder()->hasColumn('games', 'access')) {
            return [];
        }

        $price = (int) config('coins.default_vip_game_price');

        $rows = DB::table('games')
            ->where('access', 'vip')
            ->where('coin_price', 0)
            ->orderBy('id')
            ->get(['id', 'title']);

        $plan = [];

        foreach ($rows as $game) {
            $plan[] = ['id' => $game->id, 'title' => $game->title, 'price' => $price];
        }

        return $plan;
    }

    private function convertCodes(): int
    {
        $pending = $this->codePlan();

        if ($pending === []) {
            return 0;
        }

        $owner = User::query()->where('role', UserRole::Admin)->orderBy('id')->first()
            ?? User::query()->orderBy('id')->firstOrFail();

        $byAmount = collect($pending)->groupBy('coins');
        $now = now();
        $count = 0;

        foreach ($byAmount as $coins => $rows) {
            $batch = CoinCodeBatch::query()->create([
                'name' => 'VIP geçişi · '.$coins.' coin',
                'quantity' => $rows->count(),
                'coin_amount' => (int) $coins,
                'created_by' => $owner->id,
            ]);

            CoinCode::query()->insert($rows->map(fn (array $row) => [
                'code' => $row['code'],
                'batch_id' => $batch->id,
                'coin_amount' => (int) $coins,
                'status' => CoinCodeStatus::Unused->value,
                'used_by' => null,
                'used_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            $count += $rows->count();
        }

        return $count;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function grantMembers(): array
    {
        $members = 0;
        $coins = 0;

        foreach ($this->memberPlan() as $row) {
            $member = User::query()->find($row['user_id']);

            if (! $member instanceof User) {
                continue;
            }

            $this->coins->credit(
                $member,
                $row['coins'],
                CoinTransactionType::Migration,
                note: $row['days'].' gün VIP karşılığı',
                idempotencyKey: $this->grantKey($row['user_id']),
            );

            $members++;
            $coins += $row['coins'];
        }

        return [$members, $coins];
    }

    private function priceGames(): int
    {
        $games = $this->gamePlan();

        foreach ($games as $game) {
            DB::table('games')->where('id', $game['id'])->update([
                'coin_price' => $game['price'],
                'updated_at' => now(),
            ]);
        }

        return count($games);
    }

    private function alreadyGranted(User $user): bool
    {
        return CoinTransaction::query()->where('idempotency_key', $this->grantKey($user->id))->exists();
    }

    private function grantKey(int $userId): string
    {
        return 'vip-migration:user:'.$userId;
    }

    private function remainingDays(User $user): int
    {
        $until = $user->vip_until;

        if (! $until instanceof Carbon || ! $until->isFuture()) {
            return 0;
        }

        return (int) ceil(now()->diffInDays($until, true));
    }
}
