<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoinsException;
use App\Models\CoinTransaction;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CoinServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_service_writes_the_balance(): void
    {
        $forbidden = [];

        foreach ($this->phpFiles(base_path('app')) as $file) {
            if (str_ends_with($file, 'CoinService.php')) {
                continue;
            }

            $source = file_get_contents($file);

            if (preg_match("/\\['coin_balance'\\]\\s*=|forceFill\\(\\s*\\[\\s*'coin_balance'/", $source) === 1) {
                $forbidden[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
            }
        }

        $this->assertSame([], $forbidden, 'coin_balance yalnız CoinService içinde yazılır: '.implode(', ', $forbidden));
    }

    public function test_two_debits_at_once_cannot_overdraw(): void
    {
        $user = User::factory()->create();
        $coins = app(CoinService::class);
        $coins->credit($user, 100, CoinTransactionType::Bonus);

        $spent = 0;

        foreach ([1, 2] as $attempt) {
            try {
                DB::transaction(function () use ($coins, $user): void {
                    $coins->debit($user, 80, CoinTransactionType::GameUnlock, note: 'kilit');
                });
                $spent++;
            } catch (InsufficientCoinsException) {
                // ikinci harcama bakiyeyi eksiye çekemez
            }
        }

        $this->assertSame(1, $spent);
        $this->assertSame(20, $user->fresh()->coin_balance);
    }

    public function test_the_same_idempotency_key_returns_the_first_result(): void
    {
        $user = User::factory()->create();
        $coins = app(CoinService::class);

        $first = $coins->credit($user, 40, CoinTransactionType::Bonus, idempotencyKey: 'paket-1');
        $second = $coins->credit($user->fresh(), 40, CoinTransactionType::Bonus, idempotencyKey: 'paket-1');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(40, $user->fresh()->coin_balance);
        $this->assertSame(1, CoinTransaction::query()->count());
    }

    public function test_an_insufficient_balance_throws_and_writes_nothing(): void
    {
        $user = User::factory()->create();

        try {
            app(CoinService::class)->debit($user, 5, CoinTransactionType::GameUnlock);
            $this->fail('Yetersiz bakiye sessizce geçti.');
        } catch (InsufficientCoinsException $exception) {
            $this->assertSame(0, $exception->balance);
            $this->assertSame(5, $exception->needed);
        }

        $this->assertSame(0, $user->fresh()->coin_balance);
        $this->assertSame(0, CoinTransaction::query()->count());
    }

    public function test_a_failure_inside_the_transaction_rolls_back(): void
    {
        $user = User::factory()->create();
        $coins = app(CoinService::class);

        try {
            DB::transaction(function () use ($coins, $user): void {
                $coins->credit($user, 70, CoinTransactionType::Bonus);
                throw new RuntimeException('ödeme yarım kaldı');
            });
        } catch (RuntimeException) {
            // işlem geri alınmalı
        }

        $this->assertSame(0, $user->fresh()->coin_balance);
        $this->assertSame(0, CoinTransaction::query()->count());
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $dir): array
    {
        $files = [];

        foreach (scandir($dir) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$entry;

            if (is_dir($path)) {
                $files = array_merge($files, $this->phpFiles($path));
            } elseif (str_ends_with($entry, '.php')) {
                $files[] = $path;
            }
        }

        return $files;
    }
}
