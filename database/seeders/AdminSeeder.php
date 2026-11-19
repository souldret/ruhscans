<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', '');
        $password = (string) env('ADMIN_PASSWORD', '');
        $name = (string) env('ADMIN_NAME', 'Yönetici');

        if ($email === '' || $password === '') {
            $this->command?->warn('ADMIN_EMAIL veya ADMIN_PASSWORD boş. İlk yönetici oluşturulmadı.');

            return;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name !== '' ? $name : 'Yönetici',
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ],
        );

        $admin->syncRoles([User::ROLE_ADMIN]);
    }
}
