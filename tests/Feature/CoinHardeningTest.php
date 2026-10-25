<?php

namespace Tests\Feature;

use App\Enums\CoinCodeStatus;
use App\Enums\CoinTransactionType;
use App\Mail\AdminIncidentMail;
use App\Models\AuditLog;
use App\Models\CoinCode;
use App\Models\CoinPackage;
use App\Models\CoinTransaction;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\GameUnlock;
use App\Models\User;
use App\Services\CoinAdjuster;
use App\Services\CoinClawback;
use App\Services\CoinService;
use App\Services\GameUnlocker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class CoinHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_cannot_read_someone_elses_ledger_or_unlocks(): void
    {
        $owner = User::factory()->rich()->create();
        $other = User::factory()->create();
        $game = Game::factory()->create(['coin_price' => 40]);
        app(GameUnlocker::class)->unlock($owner, $game);

        $this->actingAs($other)->get(route('account.show'))
            ->assertOk()
            ->assertDontSee($owner->email)
            ->assertDontSee($game->title);

        $this->assertSame(0, $other->fresh()->coin_balance);
        $this->assertSame(0, GameUnlock::query()->where('user_id', $other->id)->count());
    }

    public function test_a_guest_cannot_redeem_unlock_or_read_the_ledger(): void
    {
        $game = Game::factory()->create(['coin_price' => 20]);

        $this->post(route('account.redeem'), ['code' => 'YOKK-ODDD-KODD'])->assertRedirect(route('login'));
        $this->postJson(route('games.unlock', $game))->assertUnauthorized();
        $this->get(route('account.show'))->assertRedirect(route('login'));
    }

    public function test_the_client_price_is_ignored_and_the_eleventh_unlock_is_limited(): void
    {
        $user = User::factory()->rich()->create();
        $game = Game::factory()->create(['coin_price' => 80]);

        $this->actingAs($user)
            ->postJson(route('games.unlock', $game), ['price' => 1])
            ->assertStatus(422);

        $this->travel(2)->minutes();
        $this->actingAs($user)->postJson(route('games.unlock', $game))->assertOk();
        $this->assertSame(920, $user->fresh()->coin_balance);

        $buyer = User::factory()->rich()->create();
        $repeat = Game::factory()->create(['coin_price' => 0]);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($buyer)->postJson(route('games.unlock', $repeat))->assertOk();
        }

        $this->actingAs($buyer)->postJson(route('games.unlock', $repeat))->assertStatus(429);
    }

    public function test_owned_games_are_marked_in_one_query(): void
    {
        $user = User::factory()->create();
        $owned = Game::factory()->create(['coin_price' => 10, 'title' => 'Sahip Oyun']);
        Game::factory()->count(3)->create(['coin_price' => 10]);
        $owned->unlocks()->create(['user_id' => $user->id, 'coins_paid' => 10]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($user)->get(route('games.index'))->assertOk()->assertSee('Sahipsin');
        $unlockQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains($query['query'], 'game_unlocks'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(1, $unlockQueries);
    }

    public function test_the_ledger_page_only_shows_the_signed_in_member(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        app(CoinService::class)->credit($user, 5, CoinTransactionType::Bonus, note: 'benim');
        app(CoinService::class)->credit($other, 9, CoinTransactionType::Bonus, note: 'baskasinin');

        $this->actingAs($user)->get(route('account.show'))
            ->assertOk()
            ->assertSee('benim')
            ->assertDontSee('baskasinin');
    }

    public function test_adjustments_refunds_prices_and_packages_are_audited_and_undeletable(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $this->actingAs($admin);

        app(CoinAdjuster::class)->adjust($admin, $member, 30, 'destek');
        $this->assertSame(30, $member->fresh()->coin_balance);
        $this->assertDatabaseHas('audit_logs', ['action' => 'coin.adjusted', 'reason' => 'destek']);

        $this->expectException(InvalidArgumentException::class);
        app(CoinAdjuster::class)->adjust($admin, $member, -5, ' ');
    }

    public function test_a_refund_and_a_price_change_leave_an_audit_row(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->rich()->create();
        $game = Game::factory()->create(['coin_price' => 25]);
        $unlock = app(GameUnlocker::class)->unlock($member, $game);

        app(GameUnlocker::class)->refund($admin, $unlock, 'yanlis oyun');
        $game->update(['coin_price' => 40]);
        CoinPackage::factory()->create();

        $this->assertDatabaseHas('audit_logs', ['action' => 'game.refunded', 'reason' => 'yanlis oyun']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'game.price_changed']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'package.created']);
        $this->assertSame(1000, $member->fresh()->coin_balance);

        $this->expectException(RuntimeException::class);
        AuditLog::query()->first()->delete();
    }

    public function test_a_short_clawback_opens_a_review_and_a_negative_policy_draws_the_balance(): void
    {
        Mail::fake();
        config(['observability.alert_email' => 'admin@site.test', 'coins.clawback' => 'review']);
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $code = CoinCode::factory()->create(['coin_amount' => 50, 'status' => CoinCodeStatus::Used, 'used_by' => $member->id, 'used_at' => now()]);

        $this->assertSame('review', app(CoinClawback::class)->reclaim($admin, $code, 'satis iadesi'));
        $this->assertTrue($member->fresh()->coin_review);
        $this->assertSame(0, $member->fresh()->coin_balance);
        $this->assertDatabaseHas('audit_logs', ['action' => 'code.clawback_review']);
        Mail::assertSent(AdminIncidentMail::class);

        config(['coins.clawback' => 'negative']);
        $second = CoinCode::factory()->create(['coin_amount' => 20, 'status' => CoinCodeStatus::Used, 'used_by' => $member->id, 'used_at' => now()]);
        $this->assertSame('reclaimed', app(CoinClawback::class)->reclaim($admin, $second, 'ikinci iade'));
        $this->assertSame(-20, $member->fresh()->coin_balance);
        $this->assertSame(CoinTransactionType::Clawback, CoinTransaction::query()->where('type', 'clawback')->first()->type);
    }

    public function test_repeated_unlocks_and_signups_from_one_ip_alert_the_admin(): void
    {
        Mail::fake();
        config([
            'observability.alert_email' => 'admin@site.test',
            'coins.anomaly.unlock_count' => 2,
            'coins.anomaly.signup_count' => 2,
        ]);
        $user = User::factory()->rich()->create();

        foreach ([30, 40] as $price) {
            app(GameUnlocker::class)->unlock($user, Game::factory()->create(['coin_price' => $price]));
        }

        Mail::assertSent(AdminIncidentMail::class);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9']);
        foreach (['a', 'b'] as $name) {
            $this->post('/register', [
                'name' => $name,
                'email' => $name.'@uye.test',
                'password' => 'Gizli-Parola-12',
                'password_confirmation' => 'Gizli-Parola-12',
            ])->assertRedirect();
        }

        Mail::assertSent(AdminIncidentMail::class);
    }

    public function test_a_paid_download_stays_closed_until_the_game_is_unlocked(): void
    {
        Storage::fake('local');
        config(['membership.games_disk' => 'local']);
        $user = User::factory()->rich()->create();
        $game = Game::factory()->create(['coin_price' => 15]);
        $file = GameFile::factory()->create(['game_id' => $game->id, 'storage_path' => 'oyunlar/deneme.zip']);
        Storage::disk('local')->put($file->storage_path, 'oyun');

        $this->actingAs($user)->get(route('downloads.show', $file))->assertForbidden();
        app(GameUnlocker::class)->unlock($user, $game);
        $this->actingAs($user)->get(route('downloads.show', $file))->assertRedirect();
    }
}
