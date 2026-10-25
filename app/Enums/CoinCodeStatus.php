<?php

namespace App\Enums;

enum CoinCodeStatus: string
{
    case Unused = 'unused';
    case Used = 'used';
    case Disabled = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::Unused => 'Kullanılmadı',
            self::Used => 'Kullanıldı',
            self::Disabled => 'Devre dışı',
        };
    }
}
