<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    public static function from(string $value): string
    {
        $ascii = strtr($value, [
            'ş' => 's', 'Ş' => 's',
            'ı' => 'i', 'İ' => 'i', 'I' => 'i',
            'ğ' => 'g', 'Ğ' => 'g',
            'ü' => 'u', 'Ü' => 'u',
            'ö' => 'o', 'Ö' => 'o',
            'ç' => 'c', 'Ç' => 'c',
        ]);

        return Str::slug($ascii, language: 'tr');
    }

    public static function fold(string $value): string
    {
        $ascii = strtr(mb_strtolower($value), [
            'ş' => 's', 'ı' => 'i', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c',
        ]);

        return trim($ascii);
    }
}
