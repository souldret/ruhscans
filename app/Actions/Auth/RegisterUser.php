<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RegisterUser
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes) {
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);

            Role::findOrCreate(User::ROLE_UYE, 'web');
            $user->assignRole(User::ROLE_UYE);

            event(new Registered($user));

            return $user;
        });
    }
}
