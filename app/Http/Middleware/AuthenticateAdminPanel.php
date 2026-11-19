<?php

namespace App\Http\Middleware;

use Filament\Http\Middleware\Authenticate;

class AuthenticateAdminPanel extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $user = $request->user();

        if ($user !== null && $user->requiresTwoFactorSetup()) {
            if ($request->expectsJson()) {
                abort(403, 'İki adımlı doğrulama kurulmalı.');
            }

            abort(redirect()->route('admin.two-factor'));
        }

        parent::authenticate($request, $guards);
    }
}
