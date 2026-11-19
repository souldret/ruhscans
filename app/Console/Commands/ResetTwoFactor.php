<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Console\Command;

class ResetTwoFactor extends Command
{
    protected $signature = 'user:reset-2fa {email}';

    protected $description = 'Kayıp cihaz için kullanıcının 2FA sırrını ve kurtarma kodlarını siler';

    public function handle(TwoFactorService $twoFactor): int
    {
        $email = strtolower((string) $this->argument('email'));
        $user = User::query()->whereRaw('lower(email) = ?', [$email])->first();

        if ($user === null) {
            $this->error('Kullanıcı bulunamadı.');

            return self::FAILURE;
        }

        $twoFactor->reset($user);
        $this->info($user->email.' için 2FA sıfırlandı. Sonraki girişte kurulum istenecek.');

        return self::SUCCESS;
    }
}
