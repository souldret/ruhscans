<?php

namespace App\Services\Launcher;

use RuntimeException;

class LauncherSigner
{
    /**
     * @return array{payload: string, signature: string, key_id: string}
     */
    public function sign(string $payload): array
    {
        $keyId = (string) config('launcher.key_id');
        $raw = sodium_crypto_sign_detached($payload, $this->privateKey());

        return [
            'payload' => $payload,
            'signature' => base64_encode($raw),
            'key_id' => $keyId,
        ];
    }

    public function verify(string $payload, string $signature, ?string $keyId = null): bool
    {
        $raw = base64_decode($signature, true);

        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SIGN_BYTES) {
            return false;
        }

        $keys = $this->publicKeys();

        if ($keyId !== null) {
            if (! isset($keys[$keyId])) {
                return false;
            }

            return sodium_crypto_sign_verify_detached($raw, $payload, $keys[$keyId]);
        }

        foreach ($keys as $public) {
            if (sodium_crypto_sign_verify_detached($raw, $payload, $public)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{key_id: string, private_b64: string, public_b64: string}
     */
    public static function generate(?string $keyId = null): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'key_id' => $keyId ?? 'k'.substr(bin2hex(random_bytes(3)), 0, 6),
            'private_b64' => base64_encode(sodium_crypto_sign_secretkey($pair)),
            'public_b64' => base64_encode(sodium_crypto_sign_publickey($pair)),
        ];
    }

    private function privateKey(): string
    {
        $encoded = (string) config('launcher.private_key');
        $raw = base64_decode($encoded, true);

        if ($raw === false || strlen($raw) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new RuntimeException('LAUNCHER_PRIVATE_KEY geçerli bir Ed25519 özel anahtar değil.');
        }

        return $raw;
    }

    /**
     * @return array<string, string>
     */
    public function publicKeys(): array
    {
        $keys = [];
        $raw = trim((string) config('launcher.public_keys'));

        if ($raw !== '') {
            foreach (explode(',', $raw) as $piece) {
                $piece = trim($piece);
                if (! str_contains($piece, ':')) {
                    continue;
                }
                [$id, $b64] = explode(':', $piece, 2);
                $decoded = base64_decode($b64, true);
                if ($decoded !== false && strlen($decoded) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                    $keys[trim($id)] = $decoded;
                }
            }
        }

        $private = (string) config('launcher.private_key');
        $decodedPrivate = base64_decode($private, true);

        if ($decodedPrivate !== false && strlen($decodedPrivate) === SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            $id = (string) config('launcher.key_id');
            $keys[$id] = sodium_crypto_sign_publickey_from_secretkey($decodedPrivate);
        }

        return $keys;
    }
}
