<?php

namespace App\Services\TwoFactor;

use Illuminate\Support\Str;

class RecoveryCode
{
    /**
     * @return list<string>
     */
    public static function generate(int $count = 8): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $codes[] = Str::upper(Str::random(5)).'-'.Str::upper(Str::random(5));
        }

        return $codes;
    }

    /**
     * @param  list<string>  $codes
     * @return list<string>
     */
    public static function hash(array $codes): array
    {
        return array_map(fn (string $code): string => hash('sha256', self::normalize($code)), $codes);
    }

    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
    }
}
