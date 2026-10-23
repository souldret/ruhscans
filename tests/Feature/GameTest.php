<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_hides_drafts(): void
    {
        Game::factory()->create(['title' => 'Açık Oyun']);
        Game::factory()->draft()->create(['title' => 'Kapalı Oyun']);

        $this->get(route('games.index'))
            ->assertOk()
            ->assertSee('Açık Oyun')
            ->assertDontSee('Kapalı Oyun');
    }

    public function test_guest_can_view_a_published_game_but_not_a_draft(): void
    {
        $published = Game::factory()->create();
        $draft = Game::factory()->draft()->create();

        $this->get(route('games.show', $published))->assertOk()->assertSee($published->title);

        $this->get(route('games.show', $draft))->assertForbidden();
    }

    public function test_admin_can_view_a_draft(): void
    {
        $draft = Game::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('games.show', $draft))
            ->assertOk();
    }
}
