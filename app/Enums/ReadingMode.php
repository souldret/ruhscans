<?php

namespace App\Enums;

enum ReadingMode: string
{
    case Webtoon = 'webtoon';
    case Ltr = 'ltr';
    case Rtl = 'rtl';

    public function label(): string
    {
        return match ($this) {
            self::Webtoon => 'Dikey kaydırma',
            self::Ltr => 'Sayfa sayfa (soldan sağa)',
            self::Rtl => 'Sayfa sayfa (sağdan sola)',
        };
    }
}
