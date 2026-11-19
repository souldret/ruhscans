<?php

namespace App\Exceptions\Economy;

use RuntimeException;

class CodeRedeemException extends RuntimeException
{
    public const GENERIC = 'Kod kullanılamadı.';

    public static function generic(): self
    {
        return new self(self::GENERIC);
    }

    public static function locked(): self
    {
        return new self('Çok fazla hatalı deneme. Bir saat sonra tekrar dene.');
    }

    public static function throttled(): self
    {
        return new self('Çok hızlı deniyorsun. Bir dakika bekle.');
    }
}
