<?php

namespace Tests\Feature;

use App\Enums\CoinTransactionType;
use App\Mail\AdminIncidentMail;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CoinAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_clean_ledger_passes_quietly(): void
    {
        $user = User::factory()->create();
        app(CoinService::class)->credit($user, 25, CoinTransactionType::Bonus);

        $this->artisan('coins:audit')->assertSuccessful();
    }

    public function test_drift_is_reported_and_mailed_to_the_admin(): void
    {
        Mail::fake();
        config(['observability.alert_email' => 'admin@ruhscans.test']);

        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['coin_balance' => 500]);

        $code = Artisan::call('coins:audit');

        $this->assertSame(1, $code);
        Mail::assertSent(AdminIncidentMail::class, fn (AdminIncidentMail $mail) => str_contains($mail->detail, $user->email));
    }

    public function test_the_audit_runs_every_day(): void
    {
        $this->assertNotEmpty(collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains((string) $event->command, 'coins:audit')));
    }
}
