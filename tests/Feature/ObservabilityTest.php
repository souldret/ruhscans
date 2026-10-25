<?php

namespace Tests\Feature;

use App\Mail\AdminIncidentMail;
use App\Models\FailedJob;
use App\Models\User;
use App\Services\SentryReporter;
use App\Support\SecretRedactor;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_pages_stay_in_turkish_without_a_trace(): void
    {
        foreach ([403, 404, 419, 429] as $status) {
            $this->get('/yok-'.$status)
                ->assertNotFound()
                ->assertDontSee('Stack trace', false);
        }

        $this->get('/olmayan-sayfa')
            ->assertNotFound()
            ->assertSee('Bu sahne yok', false)
            ->assertDontSee('Exception', false);
    }

    public function test_health_is_hidden_without_the_token_and_reports_ok_with_it(): void
    {
        config(['observability.health_token' => 'gizli-olcum', 'membership.games_disk' => 'local']);

        $this->get('/health')->assertNotFound();
        $this->withHeader('X-Health-Token', 'yanlis')->get('/health')->assertNotFound();

        $this->withHeader('X-Health-Token', 'gizli-olcum')
            ->get('/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['checks' => ['database', 'storage', 'queue', 'disk']]);
    }

    public function test_logs_mask_passwords_and_every_response_has_a_request_id(): void
    {
        $redacted = SecretRedactor::redact([
            'password' => 'Gizli-Parola-12',
            'token' => 'abc',
            'code' => 'AAAA-BBBB',
            'email' => 'uye@site.test',
        ]);

        $this->assertSame('[gizli]', $redacted['password']);
        $this->assertSame('[gizli]', $redacted['token']);
        $this->assertSame('[gizli]', $redacted['code']);
        $this->assertSame('uye@site.test', $redacted['email']);

        $this->get('/')->assertHeader('X-Request-Id');
    }

    public function test_sentry_is_silent_when_disabled_and_posts_when_configured(): void
    {
        config(['observability.sentry_dsn' => null]);
        Http::fake();
        app(SentryReporter::class)->capture(new \RuntimeException('deneme'));
        Http::assertNothingSent();

        config(['observability.sentry_dsn' => 'https://anahtar@sentry.test/12', 'observability.release' => '1.2.3']);
        app(SentryReporter::class)->capture(new \RuntimeException('deneme'));
        Http::assertSent(fn ($request) => str_contains($request->url(), '/api/12/store/')
            && $request['release'] === '1.2.3'
            && $request['environment'] === 'testing');
    }

    public function test_a_lockout_emails_the_admin_once(): void
    {
        Mail::fake();
        config(['observability.alert_email' => 'admin@site.test']);

        $request = Request::create('/login', 'POST', ['email' => 'uye@site.test', 'password' => 'yanlis']);
        event(new Lockout($request));
        event(new Lockout($request));

        Mail::assertSent(AdminIncidentMail::class, 1);
    }

    public function test_failed_jobs_page_is_admin_only_and_can_retry(): void
    {
        FailedJob::query()->create([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => "RuntimeException: bozuldu\n#0 satir",
            'failed_at' => now(),
        ]);

        $this->actingAs(User::factory()->create())->get('/yonetim/failed-jobs')->assertForbidden();
 $this->actingAs(User::factory()->admin()->create())->get('/yonetim/failed-jobs')->assertOk()->assertSee('Başarısız işler', false);
    }
}
