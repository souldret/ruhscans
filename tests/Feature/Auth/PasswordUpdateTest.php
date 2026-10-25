<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/hesap')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'Yeni-Parola-12',
                'password_confirmation' => 'Yeni-Parola-12',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('account.show'));

        $this->assertTrue(Hash::check('Yeni-Parola-12', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/hesap')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'Yeni-Parola-12',
                'password_confirmation' => 'Yeni-Parola-12',
            ]);

        $response
            ->assertSessionHasErrorsIn('updatePassword', 'current_password')
            ->assertRedirect('/hesap');
    }
}
