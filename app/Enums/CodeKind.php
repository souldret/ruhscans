<?php

namespace App\Enums;

enum CodeKind: string
{
    case RuhTasi = 'ruh_tasi';
    case Vip = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::RuhTasi => 'Ruh taşı',
            self::Vip => 'VIP',
        };
    }
}
