<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('password can be updated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'password')
        ->set('password', 'Yeni-Sifre-12!')
        ->set('password_confirmation', 'Yeni-Sifre-12!')
        ->call('updatePassword');

    $component
        ->assertHasNoErrors()
        ->assertNoRedirect();

    $this->assertTrue(Hash::check('Yeni-Sifre-12!', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('profile.update-password-form')
        ->set('current_password', 'wrong-password')
        ->set('password', 'Yeni-Sifre-12!')
        ->set('password_confirmation', 'Yeni-Sifre-12!')
        ->call('updatePassword');

    $component
        ->assertHasErrors(['current_password'])
        ->assertNoRedirect();
});
