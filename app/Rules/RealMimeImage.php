<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Dosya adına veya istemci MIME başlığına güvenmeden finfo ile gerçek türü doğrular.
 */
class RealMimeImage implements ValidationRule
{
    /** @param list<string> $mimes */
    public function __construct(
        private readonly array $mimes = ['image/jpeg', 'image/png', 'image/webp'],
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Dosya yüklenemedi. Lütfen yeniden seçin.');

            return;
        }

        $path = $value->getRealPath();
        $mime = $path ? (new \finfo(FILEINFO_MIME_TYPE))->file($path) : false;

        if (! in_array($mime, $this->mimes, true)) {
            $fail('Desteklenmeyen biçim. Yalnızca JPG, PNG ve WebP kabul edilir.');

            return;
        }

        $size = @getimagesize($path);

        if ($size === false) {
            $fail('Dosya geçerli bir görsel değil.');

            return;
        }

        $minWidth = (int) config('content.cover_min_width', 300);
        $minHeight = (int) config('content.cover_min_height', 450);
        $maxWidth = (int) config('content.cover_max_width', 6000);
        $maxHeight = (int) config('content.cover_max_height', 9000);

        if ($size[0] < $minWidth || $size[1] < $minHeight) {
            $fail("Kapak çok küçük (en az {$minWidth}×{$minHeight} piksel).");

            return;
        }

        if ($size[0] > $maxWidth || $size[1] > $maxHeight) {
            $fail("Kapak çok büyük (en fazla {$maxWidth}×{$maxHeight} piksel).");
        }
    }
}
