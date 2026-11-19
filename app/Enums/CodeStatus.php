<?php

namespace App\Enums;

enum CodeStatus: string
{
    case Aktif = 'aktif';
    case Kullanildi = 'kullanildi';
    case Iptal = 'iptal';

    public function label(): string
    {
        return match ($this) {
            self::Aktif => 'Aktif',
            self::Kullanildi => 'Kullanıldı',
            self::Iptal => 'İptal',
        };
    }
}
