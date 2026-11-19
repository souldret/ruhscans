<?php

namespace App\Enums;

enum SeriesStatus: string
{
    case Devam = 'devam';
    case Tamamlandi = 'tamamlandi';
    case AraVerdi = 'ara_verdi';
    case Birakildi = 'birakildi';

    public function label(): string
    {
        return match ($this) {
            self::Devam => 'Devam ediyor',
            self::Tamamlandi => 'Tamamlandı',
            self::AraVerdi => 'Ara verdi',
            self::Birakildi => 'Bırakıldı',
        };
    }
}
