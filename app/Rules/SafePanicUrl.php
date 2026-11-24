<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafePanicUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            $fail('Geçerli bir panik adresi girin.');

            return;
        }

        $url = trim($value);

        if (strlen($url) > 255) {
            $fail('Panik adresi en fazla 255 karakter olabilir.');

            return;
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
            $fail('Panik adresi yalnızca http:// veya https:// ile başlayan geçerli bir adres olmalıdır.');

            return;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            $fail('Panik adresi yalnızca http:// veya https:// şemasını kullanabilir.');

            return;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            $fail('Panik adresinde kullanıcı bilgisi bulunamaz.');

            return;
        }

        $host = strtolower($parts['host']);

        if ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            $fail('Yerel veya dahili adresler panik hedefi olamaz.');

            return;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $isPublic = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

            if ($isPublic === false) {
                $fail('Özel veya yerel IP adresleri panik hedefi olamaz.');

                return;
            }
        }
    }
}
