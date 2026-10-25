<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoinsException;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_balance_moves_only_through_the_ledger(): void
    {
        $user = User::factory()->create();
        $coins = app(CoinService::class);

        $coins->credit($user, 100, CoinTransactionType::Bonus, note: 'hoş geldin');

        $this->assertSame(100, $user->fresh()->coin_balance);
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'amount' => 100,
            'balance_after' => 100,
        ]);
    }

    public function test_the_balance_cannot_drop_below_zero(): void
    {
        $user = User::factory()->create();

        $this->expectException(InsufficientCoinsException::class);

        app(CoinService::class)->debit($user, 1, CoinTransactionType::AdminAdjust);
    }

    public function test_only_admins_open_the_panel(): void
    {
        $this->actingAs(User::factory()->create())->get('/yonetim')->assertForbidden();

 $this->actingAs(User::factory()->admin()->create())->get('/yonetim')->assertOk();
    }

    public function test_email_verification_can_be_switched_off(): void
    {
        config(['membership.verification' => false]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('account.show'));
    }

    public function test_email_verification_blocks_unverified_users_when_enabled(): void
    {
        config(['membership.verification' => true]);

        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }
}
