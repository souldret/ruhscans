<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class CoinLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_ledger_row_cannot_be_changed_or_removed(): void
    {
        $user = User::factory()->create();
        $row = app(CoinService::class)->credit($user, 10, CoinTransactionType::Bonus);

        $this->expectException(LogicException::class);
        $row->delete();
    }

    public function test_credit_is_idempotent_for_the_same_key(): void
    {
        $user = User::factory()->create();
        $coins = app(CoinService::class);

        $first = $coins->credit($user, 50, CoinTransactionType::Bonus, idempotencyKey: 'bonus-1');
        $second = $coins->credit($user->fresh(), 50, CoinTransactionType::Bonus, idempotencyKey: 'bonus-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(50, $user->fresh()->coin_balance);
        $this->assertSame(1, CoinTransaction::query()->count());
    }
}
