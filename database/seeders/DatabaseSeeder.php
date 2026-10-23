<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Yönetici',
            'email' => 'admin@ruhscans.test',
        ]);

        User::factory()->create([
            'name' => 'Deneme Üye',
            'email' => 'uye@ruhscans.test',
        ]);

        Game::factory()->count(6)->create([
            'uploaded_by' => $admin->id,
        ]);

        Game::factory()->draft()->create([
            'title' => 'Taslak Oyun',
            'slug' => 'taslak-oyun',
            'uploaded_by' => $admin->id,
        ]);
    }
}
