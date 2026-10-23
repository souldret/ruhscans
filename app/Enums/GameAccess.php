<?php

namespace App\Enums;

enum GameAccess: string
{
    case Free = 'free';
    case Vip = 'vip';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Ücretsiz',
            self::Vip => 'VIP',
        };
    }
}
