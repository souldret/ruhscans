<?php

namespace App\Filament\Support;

use Filament\Forms;

class UploadFields
{
    public static function zip(string $name, string $label): Forms\Components\FileUpload
    {
        $limit = min(
            (int) config('content.max_zip_kilobytes', 204800),
            (int) config('content.direct_upload_threshold_kb', 100000),
        );

        return Forms\Components\FileUpload::make($name)
            ->label($label)
            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])
            ->maxSize($limit)
            ->disk('local')
            ->directory('chapter-zips')
            ->helperText(sprintf(
                'Bu alan %d MB altındaki ZIP’ler içindir. Daha büyük dosyaları aşağıdaki "Büyük ZIP (doğrudan R2)" alanıyla yükleyin; Cloudflare 100 MB üstündeki istekleri keser.',
                (int) floor($limit / 1024),
            ));
    }

    public static function adultConsent(): Forms\Components\Checkbox
    {
        return Forms\Components\Checkbox::make('adult_characters_confirmed')
            ->label('Tüm karakterler yetişkindir; reşit olmayan karakter içeren içerik yasaktır')
            ->accepted()
            ->dehydrated(false);
    }
}
