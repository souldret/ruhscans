<?php

namespace App\Services;

use App\Models\User;
use App\Services\TwoFactor\RecoveryCode;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa) {}

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function qrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            (string) config('app.name'),
            $user->email,
            $secret,
        );
    }

    public function verify(string $secret, string $code): bool
    {
        return (bool) $this->google2fa->verifyKey($secret, $code);
    }

    /**
     * @return list<string>|null
     */
    public function confirm(User $user, string $secret, string $code): ?array
    {
        if (! $this->verify($secret, $code)) {
            return null;
        }

        $codes = RecoveryCode::generate();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => RecoveryCode::hash($codes),
        ])->save();

        return $codes;
    }

    public function consumeRecoveryCode(User $user, string $code): bool
    {
        $hashes = $user->two_factor_recovery_codes;

        if (! is_array($hashes) || $hashes === []) {
            return false;
        }

        $needle = hash('sha256', RecoveryCode::normalize($code));
        $remaining = array_values(array_filter(
            $hashes,
            fn (mixed $hash): bool => is_string($hash) && hash_equals($hash, $needle) === false,
        ));

        if (count($remaining) === count($hashes)) {
            return false;
        }

        $user->forceFill([
            'two_factor_recovery_codes' => $remaining,
        ])->save();

        return true;
    }

    public function reset(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }
}
