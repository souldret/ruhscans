<?php

namespace App\Filament\Support;

use Filament\Forms;

class UploadFields
{
    public static function zip(string $name, string $label): Forms\Components\FileUpload
    {
        $limit = (int) config('content.max_zip_kilobytes');

        return Forms\Components\FileUpload::make($name)
            ->label($label)
            ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])
            ->maxSize($limit)
            ->disk('local')
            ->directory('chapter-zips')
            ->visibility('private')
            ->helperText(sprintf(
                'Üst sınır %d MB (CONTENT_MAX_ZIP_KB). Cloudflare 100 MB üstündeki yüklemeleri keser.',
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
