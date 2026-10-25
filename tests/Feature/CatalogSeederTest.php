<?php

namespace Tests\Feature;

use App\Models\CoinCode;
use App\Models\CoinCodeBatch;
use App\Models\Download;
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
        $this->assertSame(1, User::query()->where('coin_balance', '>', 0)->count());
        $this->assertTrue(User::query()->where('email', 'admin@ruhscans.test')->first()->isAdmin());

        $this->assertSame(40, Game::query()->count());
        $this->assertSame(20, Game::query()->where('coin_price', 0)->count());
        $this->assertSame(20, Game::query()->where('coin_price', '>', 0)->count());
        $this->assertSame(12, Tag::query()->count());
        $this->assertSame(1, Game::query()->whereNull('cover_path')->count());

        $this->assertSame(3, CoinCodeBatch::query()->count());
        $this->assertSame(66, CoinCode::query()->count());
        $this->assertSame(6, CoinCode::query()->where('code', 'like', '%E2E2%')->where('status', 'unused')->count());
        $this->assertSame(9, CoinCode::query()->where('status', 'used')->count());
        $this->assertSame(3, CoinCode::query()->where('status', 'disabled')->count());
        $this->assertGreaterThan(0, Download::query()->count());

        $this->assertTrue(Game::query()->whereHas('files')->count() === 40);
    }
}
