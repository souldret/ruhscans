<?php

namespace App\Enums;

enum SupportThreadStatus: string
{
    case Open = 'open';
    case Answered = 'answered';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Açık',
            self::Answered => 'Yanıtlandı',
            self::Closed => 'Kapalı',
        };
    }
}
