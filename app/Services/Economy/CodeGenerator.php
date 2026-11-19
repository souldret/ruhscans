<?php

namespace App\Services\Economy;

use App\Enums\CodeKind;
use App\Enums\CodeStatus;
use App\Models\CodeBatch;
use App\Models\RedeemCode;
use App\Models\User;
use Illuminate\Support\Carbon;

class CodeGenerator
{
    public function __construct(private readonly CodeHasher $hasher) {}

    /**
     * @return array{batch: CodeBatch, codes: list<array{kod: string, tur: string, deger: int}>}
     */
    public function generate(
        int $count,
        CodeKind $kind,
        int $value,
        ?Carbon $expiresAt,
        string $name,
        ?User $creator = null,
    ): array {
        if ($count < 1 || $count > 5000) {
            throw new \InvalidArgumentException('Adet 1 ile 5000 arasında olmalı.');
        }

        if ($value < 1) {
            throw new \InvalidArgumentException('Değer en az 1 olmalı.');
        }

        $batch = CodeBatch::query()->create([
            'ad' => $name,
            'tur' => $kind,
            'deger' => $value,
            'son_kullanma' => $expiresAt,
            'adet' => $count,
            'olusturan_user_id' => $creator?->id,
        ]);

        $seen = [];
        $rows = [];
        $plaintext = [];

        while (count($plaintext) < $count) {
            $plain = $this->mint();
            $normalized = $this->hasher->normalize($plain);

            if (isset($seen[$normalized])) {
                continue;
            }

            $hash = $this->hasher->hash($normalized);

            if (RedeemCode::query()->where('kod_hash', $hash)->exists()) {
                continue;
            }

            $seen[$normalized] = true;
            $plaintext[] = [
                'kod' => $plain,
                'tur' => $kind->value,
                'deger' => $value,
            ];
            $rows[] = [
                'batch_id' => $batch->id,
                'tur' => $kind->value,
                'deger' => $value,
                'son_kullanma' => $expiresAt,
                'durum' => CodeStatus::Aktif->value,
                'kod_hash' => $hash,
                'son_dort' => substr($normalized, -4),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            RedeemCode::query()->insert($chunk);
        }

        return ['batch' => $batch, 'codes' => $plaintext];
    }

    public function mint(): string
    {
        $alphabet = CodeHasher::ALPHABET;
        $length = strlen($alphabet);
        $limit = intdiv(256, $length) * $length;
        $groups = [];

        foreach (range(1, 4) as $ignored) {
            $group = '';

            foreach (range(1, 4) as $alsoIgnored) {
                do {
                    $byte = ord(random_bytes(1));
                } while ($byte >= $limit);

                $group .= $alphabet[$byte % $length];
            }

            $groups[] = $group;
        }

        return 'RUH-'.implode('-', $groups);
    }

    public function csv(array $codes): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['kod', 'tur', 'deger']);

        foreach ($codes as $code) {
            fputcsv($handle, [$code['kod'], $code['tur'], $code['deger']]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
