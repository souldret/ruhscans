<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Enums\VipCodeStatus;
use App\Models\CoinCode;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\VipCode;
use App\Services\VipCoinMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class VipCoinMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dry_run_changes_nothing(): void
    {
        $this->unusedCode(30);

        Artisan::call('coins:migrate-vip', ['--dry-run' => true]);

        $this->assertSame(0, CoinCode::query()->count());
        $this->assertSame(0, CoinTransaction::query()->count());
        $this->assertStringContainsString('Dry-run', Artisan::output());
    }

    public function test_the_rate_turns_days_into_whole_coins(): void
    {
        config(['coins.vip_day_to_coin' => 2]);

        $code = $this->unusedCode(30);
        $member = User::factory()->create(['vip_until' => now()->addDays(10)]);

        Artisan::call('coins:migrate-vip');

        $this->assertSame(60, CoinCode::query()->where('code', $code->code)->firstOrFail()->coin_amount);
        $this->assertSame(20, $member->fresh()->coin_balance);
        $this->assertSame(CoinTransactionType::Migration, CoinTransaction::query()->firstOrFail()->type);
    }

    public function test_running_it_twice_changes_nothing_the_second_time(): void
    {
        config(['coins.vip_day_to_coin' => 3]);
        $this->unusedCode(10);
        $member = User::factory()->create(['vip_until' => now()->addDays(5)]);

        Artisan::call('coins:migrate-vip');
        $codes = CoinCode::query()->count();
        $balance = $member->fresh()->coin_balance;

        Artisan::call('coins:migrate-vip');

        $this->assertSame($codes, CoinCode::query()->count());
        $this->assertSame($balance, $member->fresh()->coin_balance);
        $this->assertSame(1, CoinTransaction::query()->count());
    }

    public function test_used_codes_and_expired_members_are_left_alone(): void
    {
        config(['coins.vip_day_to_coin' => 2]);

        $used = VipCode::factory()->create(['status' => VipCodeStatus::Used, 'duration_days' => 30]);
        $expired = User::factory()->create(['vip_until' => now()->subDay()]);

        Artisan::call('coins:migrate-vip');

        $this->assertNull(CoinCode::query()->where('code', $used->code)->first());
        $this->assertSame(0, $expired->fresh()->coin_balance);
    }

    public function test_members_are_skipped_when_nobody_is_live(): void
    {
        $this->assertSame([], app(VipCoinMigration::class)->plan()['members']);
    }

    private function unusedCode(int $days): VipCode
    {
        return VipCode::factory()->create([
            'status' => VipCodeStatus::Unused,
            'duration_days' => $days,
        ]);
    }
}
