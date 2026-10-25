<?php

namespace App\Services;

use App\Enums\CoinCodeStatus;
use App\Enums\CoinTransactionType;
use App\Mail\CoinReceiptMail;
use App\Models\CodeAttempt;
use App\Models\CoinCode;
use App\Models\User;
use App\Support\VipCodeNormalizer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CoinCodeRedeemer
{
    public const FAILURE_MESSAGE = 'Kod geçersiz veya daha önce kullanılmış.';

    public function __construct(private readonly CoinService $coins) {}

    public function redeem(User $user, string $rawCode, ?string $ip): int
    {
        $this->assertNotLocked($user, $ip);
        $this->hitRateLimit($user, $ip);

        $code = VipCodeNormalizer::normalize($rawCode);
 $stored = str_replace('-', '', $code);

        try {
            $balance = DB::transaction(function () use ($user, $code, $stored): int {
 $coinCode = CoinCode::query()
 ->where('code', $code)
 ->orWhere('code', $stored)
 ->lockForUpdate()
 ->first();

                if ($coinCode === null || $coinCode->status !== CoinCodeStatus::Unused) {
                    throw ValidationException::withMessages(['code' => self::FAILURE_MESSAGE]);
                }

                $coinCode->forceFill([
                    'status' => CoinCodeStatus::Used,
                    'used_by' => $user->id,
                    'used_at' => now(),
                ])->save();

                $transaction = $this->coins->credit(
                    $user,
                    $coinCode->coin_amount,
                    CoinTransactionType::CodeRedeem,
                    ModelRef::of($coinCode),
                    'Kod '.$coinCode->code,
                    'coin-code:'.$coinCode->id,
                );

                return $transaction->balance_after;
            });
        } catch (ValidationException $exception) {
            $this->recordFailure($user, $ip);

            throw $exception;
        }

        app(CoinAnomaly::class)->redemption($user, $ip);

        if (config('coins.receipts')) {
            Mail::to($user)->send(new CoinReceiptMail($user, 'Coin yüklendi', $balance.' coin bakiyene eklendi.', $balance));
        }

        return $balance;
    }

    public function assertNotLocked(User $user, ?string $ip): void
    {
        if (Cache::has($this->lockKey($user, $ip))) {
            throw ValidationException::withMessages([
                'code' => 'Çok fazla hatalı deneme. Bir saat sonra yeniden dene.',
            ]);
        }
    }

    public function recordFailure(User $user, ?string $ip): void
    {
        CodeAttempt::query()->create([
            'user_id' => $user->id,
            'ip' => $ip,
        ]);

        $since = now()->subHour();
        $byUser = CodeAttempt::query()->where('user_id', $user->id)->where('created_at', '>=', $since)->count();
        $byIp = $ip
            ? CodeAttempt::query()->where('ip', $ip)->where('created_at', '>=', $since)->count()
            : 0;

        if ($byUser >= 20 || $byIp >= 20) {
            Cache::put($this->lockKey($user, $ip), true, now()->addHour());
        }
    }

    private function hitRateLimit(User $user, ?string $ip): void
    {
        $key = 'coin-code-redeem:'.$user->id.':'.($ip ?: 'unknown');

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Çok hızlı deniyorsun. Bir dakika bekle.',
            ]);
        }

        RateLimiter::hit($key, 60);
    }

    private function lockKey(User $user, ?string $ip): string
    {
        return 'coin-code-lock:'.$user->id.':'.($ip ?: 'unknown');
    }
}
