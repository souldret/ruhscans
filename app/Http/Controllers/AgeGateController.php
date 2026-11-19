<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureAdult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgeGateController extends Controller
{
    public function confirm(Request $request): RedirectResponse
    {
        $target = $request->string('intended')->toString();

        if ($target === '' || ! str_starts_with($target, $request->getSchemeAndHttpHost())) {
            $target = route('home');
        }

        return redirect()->to($target)->cookie(
            EnsureAdult::COOKIE,
            '1',
            60 * 24 * 30,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax',
        );
    }
}
