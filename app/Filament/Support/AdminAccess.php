<?php

namespace App\Filament\Support;

use App\Models\User;

class AdminAccess
{
    public static function admin(): bool
    {
        return auth()->user()?->hasRole(User::ROLE_ADMIN) ?? false;
    }

    public static function staff(): bool
    {
        return auth()->user()?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_CEVIRI]) ?? false;
    }
}
