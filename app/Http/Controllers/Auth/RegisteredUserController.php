<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegistrationRequest;
use App\Models\User;
use App\Services\CoinAnomaly;
use App\Services\Turnstile;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegistrationRequest $request, Turnstile $turnstile): RedirectResponse
    {
        if (! $turnstile->passes($request->string('cf-turnstile-response')->toString(), $request->ip())) {
            return back()->withInput()->withErrors(['email' => 'Doğrulama tamamlanmadı.']);
        }

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        event(new Registered($user));
        app(CoinAnomaly::class)->registration($request->ip());

        Auth::login($user);

        if (! config('membership.verification')) {
            $user->markEmailAsVerified();
        }

        return redirect(route('dashboard', absolute: false));
    }
}
