<?php

namespace App\Enums;

enum TranslationStatus: string
{
    case Ongoing = 'ongoing';
    case Complete = 'complete';

    public function label(): string
    {
        return match ($this) {
            self::Ongoing => 'Devam ediyor',
            self::Complete => 'Tamamlandı',
        };
    }
}
