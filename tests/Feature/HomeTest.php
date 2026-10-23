<?php

namespace Tests\Feature;

use App\Models\Game;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_lists_only_published_games(): void
    {
        $published = Game::factory()->create(['title' => 'Yayındaki Oyun']);
        Game::factory()->draft()->create(['title' => 'Gizli Taslak']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Yayındaki Oyun')
            ->assertDontSee('Gizli Taslak')
            ->assertSee((string) $published->downloads_count);
    }
}
