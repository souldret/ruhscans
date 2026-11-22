<?php

namespace App\Services\Content;

use Aws\S3\S3Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cloudflare 100 MB istek gövdesi sınırını aşan ZIP'ler için tarayıcıya doğrudan
 * R2 PUT izni verir. Dosya sunucudan geçmez; sunucu yalnızca imzalı adres üretir
 * ve yükleme bitince işi kuyruğa alır.
 */
class DirectZipUpload
{
    private const PART_SIZE = 16777216; // 16 MB — R2 asgari parça boyutunun üstünde.

    /** @return array{url: string, path: string, token: string} */
    public function presign(int $size, string $filename): array
    {
        $this->assertThreshold($size);

        $path = $this->path($filename);
        $client = $this->client();
        $config = config('filesystems.disks.r2');

        $request = $client->createPresignedRequest(
            $client->getCommand('PutObject', [
                'Bucket' => $config['bucket'],
                'Key' => $path,
                'ContentType' => 'application/zip',
            ]),
            now()->addMinutes((int) config('content.direct_upload_ttl_minutes', 30)),
        );

        return [
            'url' => (string) $request->getUri(),
            'path' => $path,
            'token' => $this->reserve($path),
        ];
    }

    /**
     * @return array{path: string, upload_id: string, part_size: int, parts: list<array{part_number: int, url: string}>, token: string}
     */
    public function startMultipart(int $size, string $filename): array
    {
        $this->assertThreshold($size);

        $path = $this->path($filename);
        $client = $this->client();
        $config = config('filesystems.disks.r2');

        $result = $client->createMultipartUpload([
            'Bucket' => $config['bucket'],
            'Key' => $path,
            'ContentType' => 'application/zip',
        ]);

        $uploadId = (string) $result['UploadId'];
        $partSize = self::PART_SIZE;
        $count = max(1, (int) ceil($size / $partSize));
        $parts = [];

        for ($number = 1; $number <= $count; $number++) {
            $request = $client->createPresignedRequest(
                $client->getCommand('UploadPart', [
                    'Bucket' => $config['bucket'],
                    'Key' => $path,
                    'UploadId' => $uploadId,
                    'PartNumber' => $number,
                ]),
                now()->addMinutes((int) config('content.direct_upload_ttl_minutes', 30)),
            );

            $parts[] = [
                'part_number' => $number,
                'url' => (string) $request->getUri(),
            ];
        }

        return [
            'path' => $path,
            'upload_id' => $uploadId,
            'part_size' => $partSize,
            'parts' => $parts,
            'token' => $this->reserve($path),
        ];
    }

    /**
     * @param list<array{part_number: int, etag: string}> $parts
     */
    public function completeMultipart(string $path, string $uploadId, array $parts): void
    {
        if ($parts === []) {
            throw new RuntimeException('Parça listesi boş.');
        }

        $client = $this->client();
        $config = config('filesystems.disks.r2');

        $client->completeMultipartUpload([
            'Bucket' => $config['bucket'],
            'Key' => $path,
            'UploadId' => $uploadId,
            'MultipartUpload' => [
                'Parts' => array_map(
                    fn (array $part): array => [
                        'ETag' => (string) $part['etag'],
                        'PartNumber' => (int) $part['part_number'],
                    ],
                    $parts,
                ),
            ],
        ]);
    }

    public function abortMultipart(string $path, string $uploadId): void
    {
        $client = $this->client();
        $config = config('filesystems.disks.r2');

        $client->abortMultipartUpload([
            'Bucket' => $config['bucket'],
            'Key' => $path,
            'UploadId' => $uploadId,
        ]);
    }

    public function reserve(string $path): string
    {
        $token = Str::random(40);

        Cache::put('direct-zip:'.$token, $path, now()->addMinutes(
            (int) config('content.direct_upload_ttl_minutes', 30),
        ));

        return $token;
    }

    public function take(string $token): ?string
    {
        $key = 'direct-zip:'.$token;
        $path = Cache::get($key);

        if (is_string($path)) {
            Cache::forget($key);
        }

        return is_string($path) ? $path : null;
    }

    private function assertThreshold(int $size): void
    {
        $threshold = (int) config('content.direct_upload_threshold_kb', 100000) * 1024;

        if ($size <= $threshold) {
            throw new RuntimeException('Bu boyut için normal ZIP yükleme alanını kullanın.');
        }
    }

    private function path(string $filename): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filename)) ?: 'upload.zip';

        return 'chapter-zips/pending/'.Str::uuid().'-'.$safe;
    }

    private function client(): S3Client
    {
        $config = config('filesystems.disks.r2');

        if (blank($config['bucket'] ?? null) || blank($config['key'] ?? null) || blank($config['secret'] ?? null)) {
            throw new RuntimeException('R2 yapılandırması eksik.');
        }

        return new S3Client([
            'version' => 'latest',
            'region' => $config['region'] ?: 'auto',
            'endpoint' => $config['endpoint'],
            'use_path_style_endpoint' => (bool) ($config['use_path_style_endpoint'] ?? false),
            'credentials' => [
                'key' => $config['key'],
                'secret' => $config['secret'],
            ],
        ]);
    }
}
