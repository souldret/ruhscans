<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NotDisposableEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! str_contains($value, '@')) {
            return;
        }

        $domain = strtolower(substr($value, (int) strrpos($value, '@') + 1));
        $blocked = config('membership.disposable_domains', []);

        if (is_array($blocked) && in_array($domain, $blocked, true)) {
            $fail('Tek kullanımlık e-posta adresi kabul edilmiyor.');
        }
    }
}
