<?php

namespace Tests\Feature;

use App\Enums\CoinCodeStatus;
use App\Enums\CoinTransactionType;
use App\Models\CoinCode;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Services\CoinCodeRedeemer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CoinCodeRedeemTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_code_credits_the_balance_and_the_ledger(): void
    {
        $user = User::factory()->create();
        $code = $this->code(150);

        $this->actingAs($user)
            ->post(route('account.redeem'), ['code' => strtolower(' '.$code->code.' ')])
            ->assertRedirect(route('account.show'))
            ->assertSessionHas('celebrate', true);

        $this->assertSame(150, $user->fresh()->coin_balance);
        $this->assertSame(CoinCodeStatus::Used, $code->fresh()->status);

        $row = CoinTransaction::query()->firstOrFail();
        $this->assertSame(CoinTransactionType::CodeRedeem, $row->type);
        $this->assertSame(150, $row->amount);
        $this->assertSame(150, $row->balance_after);
        $this->assertSame(CoinCode::class, $row->reference_type);
        $this->assertSame($code->id, $row->reference_id);
    }

    public function test_a_used_code_and_a_disabled_code_share_the_same_error(): void
    {
        $user = User::factory()->create();
        $used = $this->code(100, CoinCodeStatus::Used);
        $disabled = $this->code(100, CoinCodeStatus::Disabled);

        foreach ([$used->code, $disabled->code, 'YOK-BOYLE-BIR-KOD'] as $value) {
            $this->actingAs($user)
                ->post(route('account.redeem'), ['code' => $value])
                ->assertSessionHasErrors(['code' => CoinCodeRedeemer::FAILURE_MESSAGE]);
        }

        $this->assertSame(0, $user->fresh()->coin_balance);
        $this->assertSame(0, CoinTransaction::query()->count());
    }

    public function test_the_same_code_used_twice_at_once_credits_once(): void
    {
        $code = $this->code(80);
        $first = User::factory()->create();
        $second = User::factory()->create();

        DB::transaction(function () use ($first, $code): void {
            app(CoinCodeRedeemer::class)->redeem($first, $code->code, '10.0.0.1');
        });

        try {
            DB::transaction(function () use ($second, $code): void {
                app(CoinCodeRedeemer::class)->redeem($second, $code->code, '10.0.0.2');
            });
            $this->fail('Aynı kod iki kez geçti.');
        } catch (ValidationException $exception) {
            $this->assertSame(CoinCodeRedeemer::FAILURE_MESSAGE, $exception->errors()['code'][0]);
        }

        $this->assertSame(80, $first->fresh()->coin_balance);
        $this->assertSame(0, $second->fresh()->coin_balance);
        $this->assertSame(1, CoinTransaction::query()->count());
    }

    public function test_the_sixth_attempt_in_a_minute_is_refused(): void
    {
        $user = User::factory()->create();
        $code = $this->code(50);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post(route('account.redeem'), ['code' => 'YOK'.$i]);
        }

        $this->actingAs($user)
            ->post(route('account.redeem'), ['code' => $code->code])
            ->assertStatus(429);

        $this->assertSame(CoinCodeStatus::Unused, $code->fresh()->status);
        $this->assertSame(0, $user->fresh()->coin_balance);
    }

    public function test_twenty_failures_lock_the_user_for_an_hour(): void
    {
        $user = User::factory()->create();
        $code = $this->code(50);

        for ($i = 0; $i < 20; $i++) {
            $this->travel(61)->seconds();
            $this->actingAs($user)->post(route('account.redeem'), ['code' => 'YOK'.$i]);
        }

        $this->actingAs($user)
            ->post(route('account.redeem'), ['code' => $code->code])
            ->assertSessionHasErrors(['code' => 'Çok fazla hatalı deneme. Bir saat sonra yeniden dene.']);

        $this->assertSame(CoinCodeStatus::Unused, $code->fresh()->status);
    }

    private function code(int $coins, CoinCodeStatus $status = CoinCodeStatus::Unused): CoinCode
    {
        return CoinCode::factory()->create([
            'coin_amount' => $coins,
            'status' => $status,
        ]);
    }
}
