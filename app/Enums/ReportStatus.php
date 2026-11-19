<?php

namespace App\Enums;

enum ReportStatus: string
{
    case Bekliyor = 'bekliyor';
    case Incelendi = 'incelendi';
    case Reddedildi = 'reddedildi';

    public function label(): string
    {
        return match ($this) {
            self::Bekliyor => 'Bekliyor',
            self::Incelendi => 'İncelendi',
            self::Reddedildi => 'Reddedildi',
        };
    }
}
