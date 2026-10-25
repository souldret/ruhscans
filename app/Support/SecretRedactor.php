<?php

namespace App\Support;

class SecretRedactor
{
    public static function redact(mixed $value, ?string $key = null): mixed
    {
        if (is_string($key) && self::sensitive($key)) {
            return '[gizli]';
        }

        if (! is_array($value)) {
            return $value;
        }

        $redacted = [];

        foreach ($value as $childKey => $child) {
            $redacted[$childKey] = self::redact($child, is_string($childKey) ? $childKey : null);
        }

        return $redacted;
    }

    private static function sensitive(string $key): bool
    {
        return (bool) preg_match('/password|token|authorization|secret|cookie|^code$/i', $key);
    }
}
