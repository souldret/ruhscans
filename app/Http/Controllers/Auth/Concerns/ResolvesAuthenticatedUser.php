<?php

namespace App\Http\Controllers\Auth\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

trait ResolvesAuthenticatedUser
{
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return $user->fresh() ?? $user;
    }
}
