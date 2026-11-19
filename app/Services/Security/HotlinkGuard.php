<?php

namespace App\Services\Security;

use Illuminate\Http\Request;

class HotlinkGuard
{
    /**
     * @return list<string>
     */
    public function hosts(): array
    {
        $hosts = config('security.hotlink_hosts', []);

        if (! is_array($hosts) || $hosts === []) {
            $host = parse_url((string) config('app.url'), PHP_URL_HOST);

            return $host ? [strtolower($host)] : [];
        }

        return array_values(array_unique(array_map(
            fn ($host) => strtolower((string) $host),
            $hosts,
        )));
    }

    public function allowed(?Request $request = null): bool
    {
        $request ??= request();
        $referer = $request->headers->get('referer');
        $origin = $request->headers->get('origin');

        if (! filled($referer) && ! filled($origin)) {
            return true;
        }

        $hosts = $this->hosts();

        if ($hosts === []) {
            return true;
        }

        foreach ([$referer, $origin] as $value) {
            if (! filled($value)) {
                continue;
            }

            $host = strtolower((string) parse_url($value, PHP_URL_HOST));

            if ($host === '' || ! in_array($host, $hosts, true)) {
                return false;
            }
        }

        return true;
    }
}
