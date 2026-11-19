<?php

namespace App\Services\Economy;

class CodeHasher
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function normalize(string $plain): string
    {
        $plain = strtoupper(trim($plain));
        $plain = str_replace([' ', '-', '_'], '', $plain);
        $plain = strtr($plain, [
            '0' => 'O',
            '1' => 'I',
            'L' => 'I',
        ]);

        return $plain;
    }

    public function hash(string $normalized): string
    {
        $key = (string) config('economy.hmac_key');

        if ($key === '') {
            throw new \RuntimeException('CODE_HMAC_KEY tanımlı değil.');
        }

        return hash_hmac('sha256', $normalized, $key);
    }

    public function hashPlain(string $plain): string
    {
        return $this->hash($this->normalize($plain));
    }

    public function lastFour(string $plain): string
    {
        $normalized = $this->normalize($plain);

        return substr($normalized, -4);
    }
}
