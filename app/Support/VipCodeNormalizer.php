<?php

namespace App\Support;

class VipCodeNormalizer
{
    public static function normalize(string $value): string
    {
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');

        return implode('-', str_split($clean, 4));
    }
}
