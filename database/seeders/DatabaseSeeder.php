<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            RoleSeeder::class,
            AdminSeeder::class,
        ];

        if (app()->environment('local')) {
            $seeders[] = ContentSeeder::class;
        }

        $this->call($seeders);
    }
}
