<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\GamePriceHistory;
use App\Models\GameUnlock;
use App\Models\User;
use App\Services\CoinService;
use App\Services\GameUnlocker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_with_enough_coins_unlocks_at_the_current_price(): void
    {
        $game = Game::factory()->create(['coin_price' => 100]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 250, CoinTransactionType::Bonus);

        $this->actingAs($user)
            ->postJson(route('games.unlock', $game))
            ->assertOk()
            ->assertJson(['ok' => true, 'paid' => 100, 'balance' => 150]);

        $this->actingAs($user)
            ->from(route('games.show', $game))
            ->post(route('games.unlock', $game))
            ->assertRedirect(route('games.show', $game));

        $this->assertDatabaseHas('game_unlocks', ['user_id' => $user->id, 'game_id' => $game->id, 'coins_paid' => 100]);

        $row = CoinTransaction::query()->where('type', CoinTransactionType::GameUnlock)->firstOrFail();
        $this->assertSame(-100, $row->amount);
        $this->assertSame(150, $row->balance_after);
    }

    public function test_a_short_balance_is_refused_and_nothing_is_written(): void
    {
        $game = Game::factory()->create(['coin_price' => 100]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 40, CoinTransactionType::Bonus);

        $this->actingAs($user)
            ->postJson(route('games.unlock', $game))
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'missing' => 60]);

        $this->assertSame(40, $user->fresh()->coin_balance);
        $this->assertSame(0, GameUnlock::query()->count());
    }

    public function test_unlocking_the_same_game_twice_charges_once(): void
    {
        $game = Game::factory()->create(['coin_price' => 80]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 200, CoinTransactionType::Bonus);

        $this->actingAs($user)->postJson(route('games.unlock', $game))->assertOk();
        $this->actingAs($user)->postJson(route('games.unlock', $game))->assertOk()->assertJson(['paid' => 80]);

        $this->assertSame(120, $user->fresh()->coin_balance);
        $this->assertSame(1, GameUnlock::query()->count());
        $this->assertSame(1, CoinTransaction::query()->where('type', CoinTransactionType::GameUnlock)->count());
    }

    public function test_two_requests_at_once_charge_once(): void
    {
        $game = Game::factory()->create(['coin_price' => 60]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 100, CoinTransactionType::Bonus);

        $charged = 0;

        foreach ([1, 2] as $attempt) {
            DB::transaction(function () use ($user, $game, &$charged): void {
                app(GameUnlocker::class)->unlock($user, $game);
                $charged++;
            });
        }

        $this->assertSame(2, $charged);
        $this->assertSame(40, $user->fresh()->coin_balance);
        $this->assertSame(1, GameUnlock::query()->count());
    }

    public function test_the_sale_price_applies_until_it_ends(): void
    {
        $game = Game::factory()->create([
            'coin_price' => 100,
            'sale_price' => 70,
            'sale_ends_at' => now()->addHour(),
        ]);
        $early = User::factory()->create();
        app(CoinService::class)->credit($early, 200, CoinTransactionType::Bonus);

        $this->actingAs($early)->postJson(route('games.unlock', $game))->assertJson(['paid' => 70]);

        $this->travel(2)->hours();

        $late = User::factory()->create();
        app(CoinService::class)->credit($late, 200, CoinTransactionType::Bonus);

        $this->actingAs($late)->postJson(route('games.unlock', $game))->assertJson(['paid' => 100]);
    }

    public function test_a_refund_closes_the_download_again(): void
    {
        $game = Game::factory()->create(['coin_price' => 50]);
        $file = GameFile::factory()->create(['game_id' => $game->id]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 100, CoinTransactionType::Bonus);
        $unlock = app(GameUnlocker::class)->unlock($user, $game);

        $this->actingAs($user)->get(route('downloads.show', $file))->assertRedirect();

        app(GameUnlocker::class)->refund(User::factory()->admin()->create(), $unlock, 'Yanlışlıkla açıldı');

        $this->actingAs($user)->get(route('downloads.show', $file))->assertForbidden();
        $this->assertSame(100, $user->fresh()->coin_balance);
        $this->assertSame(CoinTransactionType::Refund, CoinTransaction::query()->where('type', CoinTransactionType::Refund)->firstOrFail()->type);
    }

    public function test_a_price_change_is_recorded_and_does_not_touch_past_unlocks(): void
    {
        $game = Game::factory()->create(['coin_price' => 40]);
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 100, CoinTransactionType::Bonus);
        app(GameUnlocker::class)->unlock($user, $game);

        $game->update(['coin_price' => 90]);

        $this->assertSame(40, GameUnlock::query()->firstOrFail()->coins_paid);
        $this->assertSame(60, $user->fresh()->coin_balance);
        $this->assertDatabaseHas('game_price_history', ['game_id' => $game->id, 'old_price' => 40, 'new_price' => 90]);
        $this->assertSame(1, GamePriceHistory::query()->count());
    }
}
