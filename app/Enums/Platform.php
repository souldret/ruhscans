<?php

namespace App\Enums;

enum Platform: string
{
    case Windows = 'windows';
    case Android = 'android';
    case Mac = 'mac';
    case Linux = 'linux';
    case Patch = 'patch';

    public function label(): string
    {
        return match ($this) {
            self::Windows => 'Windows',
            self::Android => 'Android',
            self::Mac => 'Mac',
            self::Linux => 'Linux',
            self::Patch => 'Yama',
        };
    }
}
