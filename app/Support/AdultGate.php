<?php

namespace App\Support;

use Illuminate\Http\Request;

class AdultGate
{
    public const COOKIE = 'adult';

    public static function confirmed(Request $request): bool
    {
        return $request->cookie(self::COOKIE) === '1' || self::isCrawler($request);
    }

    public static function isCrawler(Request $request): bool
    {
        $agent = strtolower((string) $request->userAgent());

        foreach ([
            'googlebot',
            'bingbot',
            'yandex',
            'duckduckbot',
            'baiduspider',
            'slurp',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
            'whatsapp',
            'telegrambot',
            'applebot',
        ] as $bot) {
            if (str_contains($agent, $bot)) {
                return true;
            }
        }

        return false;
    }
}
