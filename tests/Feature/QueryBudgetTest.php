<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Support\CounterBuffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_pages_stay_within_the_query_budget(): void
    {
        Game::factory()->count(12)->create();
        $slug = Game::query()->value('slug');

        foreach ([
            route('home'),
            route('games.index'),
            route('games.index', ['q' => 'şiir']),
            route('games.show', $slug),
        ] as $url) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $this->get($url)->assertOk();

            $queries = DB::getQueryLog();
            DB::disableQueryLog();
            $database = array_sum(array_column($queries, 'time'));

            $this->assertLessThanOrEqual(10, count($queries), $url.' sorgu: '.count($queries));
            $this->assertLessThan(100, $database, $url.' veritabanı: '.$database);
        }
    }

    public function test_folded_search_matches_turkish_characters(): void
    {
        Game::factory()->create(['title' => 'Şehir Işığı', 'developer' => 'Gölge']);

        $this->get(route('games.index', ['q' => 'sehir']))->assertOk()->assertSee('Şehir Işığı');
        $this->get(route('games.index', ['q' => 'golge']))->assertOk()->assertSee('Şehir Işığı');
    }

    public function test_view_counts_flush_in_batches(): void
    {
        $game = Game::factory()->create(['view_count' => 0]);
        $buffer = app(CounterBuffer::class);

        for ($i = 0; $i < 24; $i++) {
            $buffer->add('view_count', $game->id);
        }

        $this->assertSame(0, $game->fresh()->view_count);

        $buffer->flush('view_count', $game->id);

        $this->assertSame(24, $game->fresh()->view_count);
    }
}
