<?php

namespace Tests\Feature\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_a_registered_member_can_log_in_and_reach_the_account(): void
    {
        $this->post('/register', [
            'name' => 'Ada Yılmaz',
            'email' => 'ada@example.com',
            'password' => 'Gizli-Parola-12',
            'password_confirmation' => 'Gizli-Parola-12',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'Gizli-Parola-12',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('account.show'))->assertOk();
    }

    public function test_the_sixth_login_in_a_minute_is_refused(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'yanlis-sifre',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(429);

        $this->assertGuest();
    }

    public function test_login_request_locks_after_five_attempts(): void
    {
        $user = User::factory()->create();
        $key = Str::transliterate(Str::lower($user->email).'|127.0.0.1');

        for ($i = 0; $i < 5; $i++) {
            RateLimiter::hit($key, 60);
        }

        $request = LoginRequest::create('/login', 'POST', [
            'email' => $user->email,
            'password' => 'password',
        ]);
        $request->setContainer(app())->setRedirector(app('redirect'));

        $this->expectException(ValidationException::class);
        $request->ensureIsNotRateLimited();
    }
}
