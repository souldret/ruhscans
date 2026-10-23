<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Tag;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_builds_the_catalog(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(3, User::query()->count());
        $this->assertSame(1, User::query()->vip()->count());
        $this->assertTrue(User::query()->where('email', 'admin@ruhscans.test')->first()->isAdmin());

        $this->assertSame(12, Game::query()->count());
        $this->assertSame(6, Game::query()->where('access', 'free')->count());
        $this->assertSame(6, Game::query()->where('access', 'vip')->count());
        $this->assertSame(8, Tag::query()->count());

        $this->assertTrue(Game::query()->whereHas('files')->count() === 12);
    }
}
