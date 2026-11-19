<?php

namespace App\Enums;

enum ContentLevel: string
{
    case Yumusak = 'yumusak';
    case Acik = 'acik';

    public function label(): string
    {
        return match ($this) {
            self::Yumusak => 'Yumuşak',
            self::Acik => 'Açık',
        };
    }
}
