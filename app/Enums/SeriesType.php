<?php

namespace App\Enums;

enum SeriesType: string
{
    case Manga = 'manga';
    case Manhwa = 'manhwa';
    case Manhua = 'manhua';
    case Webtoon = 'webtoon';

    public function label(): string
    {
        return match ($this) {
            self::Manga => 'Manga',
            self::Manhwa => 'Manhwa',
            self::Manhua => 'Manhua',
            self::Webtoon => 'Webtoon',
        };
    }
}
