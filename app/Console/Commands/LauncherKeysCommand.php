<?php

namespace App\Console\Commands;

use App\Services\Launcher\LauncherSigner;
use Illuminate\Console\Command;

class LauncherKeysCommand extends Command
{
    protected $signature = 'launcher:keys {--id= : Anahtar kimliği. Boşsa rastgele üretilir.}';

    protected $description = 'Launcher Ed25519 anahtar çifti üretir. Özel anahtar yalnızca sunucuda kalır.';

    public function handle(): int
    {
        $id = $this->option('id');
        $pair = LauncherSigner::generate(is_string($id) && $id !== '' ? $id : null);

        $this->line('LAUNCHER_KEY_ID='.$pair['key_id']);
        $this->line('LAUNCHER_PRIVATE_KEY='.$pair['private_b64']);
        $this->line('LAUNCHER_PUBLIC_KEYS='.$pair['key_id'].':'.$pair['public_b64']);
        $this->newLine();
        $this->comment('Özel anahtarı yalnızca .env veya gizli depoya yaz. Açık anahtarı ('.$pair['key_id'].') modüle göm.');
        $this->comment('Döndürme: yeni çifti üret, LAUNCHER_PUBLIC_KEYS listesine virgülle ekle, LAUNCHER_KEY_ID yeni kimliğe geç.');

        return self::SUCCESS;
    }
}
