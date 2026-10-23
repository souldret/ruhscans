<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_as_a_member(): void
    {
        $response = $this->post(route('register'), [
            'name' => 'Ayşe',
            'email' => 'ayse@ornek.test',
            'password' => 'parola123',
            'password_confirmation' => 'parola123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'ayse@ornek.test',
            'role' => UserRole::Member->value,
        ]);
    }

    public function test_registration_requires_a_matching_password(): void
    {
        $this->from(route('register'))
            ->post(route('register'), [
                'name' => 'Ayşe',
                'email' => 'ayse@ornek.test',
                'password' => 'parola123',
                'password_confirmation' => 'baska',
            ])
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_member_can_log_in_and_out(): void
    {
        $user = User::factory()->create([
            'email' => 'uye@ornek.test',
            'password' => 'parola123',
        ]);

        $this->post(route('login'), [
            'email' => 'uye@ornek.test',
            'password' => 'yanlis',
        ])->assertSessionHasErrors('email');

        $this->post(route('login'), [
            'email' => 'uye@ornek.test',
            'password' => 'parola123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_member_cannot_open_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/yonetim')
            ->assertForbidden();
    }

    public function test_admin_can_open_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/yonetim')
            ->assertOk();
    }
}
