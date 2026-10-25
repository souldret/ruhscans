<?php

namespace App\Support;

use RuntimeException;

class VipCodeGenerator
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function make(?string $prefix = null): string
    {
        $prefix = self::cleanPrefix($prefix);
        $body = self::randomBlock().'-'.self::randomBlock().'-'.self::randomBlock().'-'.self::randomBlock();

        return $prefix === '' ? $body : $prefix.'-'.$body;
    }

    public static function cleanPrefix(?string $prefix): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $prefix) ?? '');

        return substr($prefix, 0, 8);
    }

    private static function randomBlock(): string
    {
        $length = strlen(self::ALPHABET);
        $block = '';

        for ($i = 0; $i < 4; $i++) {
            $block .= self::ALPHABET[random_int(0, $length - 1)];
        }

        return $block;
    }

    /**
     * @return list<string>
     */
    public static function unique(int $count, ?string $prefix = null): array
    {
        if ($count < 1) {
            throw new RuntimeException('En az bir kod üretilmeli.');
        }

        $codes = [];

        while (count($codes) < $count) {
            $code = self::make($prefix);
            $codes[$code] = $code;
        }

        return array_values($codes);
    }
}
