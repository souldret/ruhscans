<?php

namespace Tests\Feature;

use App\Mail\AdminIncidentMail;
use App\Models\AuditLog;
use App\Models\Game;
use App\Models\GameFile;
use App\Models\User;
use App\Services\CoinCodeIssuer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Tests\TestCase;

class AbuseControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_disposable_email_cannot_register(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Bot',
            'email' => 'bot@mailinator.com',
            'password' => 'GizliParola12',
            'password_confirmation' => 'GizliParola12',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'bot@mailinator.com']);
    }

    public function test_turnstile_blocks_registration_when_it_is_enabled(): void
    {
        config([
            'membership.turnstile_secret' => 'gizli',
            'membership.turnstile_site' => 'site',
        ]);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->post(route('register.store'), [
            'name' => 'Bot',
            'email' => 'bot@ornek.test',
            'password' => 'GizliParola12',
            'password_confirmation' => 'GizliParola12',
            'cf-turnstile-response' => 'sahte',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseMissing('users', ['email' => 'bot@ornek.test']);
    }

    public function test_a_third_session_is_dropped(): void
    {
        config(['session.driver' => 'database', 'membership.max_sessions' => 2]);
        $user = User::factory()->create();

        DB::table('sessions')->insert([
            ['id' => 'eski', 'user_id' => $user->id, 'payload' => 'a', 'last_activity' => now()->subHour()->timestamp],
            ['id' => 'orta', 'user_id' => $user->id, 'payload' => 'b', 'last_activity' => now()->subMinute()->timestamp],
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertSame(2, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertDatabaseMissing('sessions', ['id' => 'eski']);
    }

    public function test_two_countries_in_a_short_window_alert_the_admin(): void
    {
        Mail::fake();
        config(['observability.alert_email' => 'admin@site.test']);
        $user = User::factory()->create();
        $file = $this->file();

        $this->actingAs($user)->withHeader('CF-IPCountry', 'TR')->get(route('downloads.show', $file));
        $other = $this->file();
        $this->actingAs($user)->withHeader('CF-IPCountry', 'DE')->get(route('downloads.show', $other));

        Mail::assertSent(AdminIncidentMail::class, function ($mail) {
            return $mail->title === 'İndirme anomalisi';
        });
    }

    public function test_a_bound_download_link_rejects_another_ip(): void
    {
        config(['membership.bind_download_ip' => true, 'membership.games_disk' => 'local']);
        Storage::fake('local');
        $file = $this->file();
        Storage::disk('local')->put($file->storage_path, 'yama');
        $user = User::factory()->create();

        $url = URL::temporarySignedRoute('downloads.signed', now()->addMinutes(5), [
            'file' => $file,
            'uye' => $user->id,
            'ip' => hash('sha256', '203.0.113.8'),
        ]);

        $this->get($url)->assertForbidden();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.8'])->get($url)->assertOk();
    }

    public function test_issuing_codes_and_deleting_a_user_are_logged_and_logs_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        app(CoinCodeIssuer::class)->issue($admin, 'Parti', 2, 7);

        $this->assertDatabaseHas('audit_logs', ['action' => 'code.issued', 'actor_id' => $admin->id]);

        $member = User::factory()->create();
        $this->actingAs($member)->delete(route('profile.destroy'), ['password' => 'password']);

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted', 'subject_id' => $member->id]);

        $this->expectException(RuntimeException::class);
        AuditLog::query()->first()->delete();
    }

    private function file(): GameFile
    {
        $game = Game::factory()->create(['coin_price' => 0, 'download_count' => 0]);

        return GameFile::factory()->create([
            'game_id' => $game->id,
            'storage_path' => 'oyunlar/'.$game->slug.'/windows.zip',
        ]);
    }
}
