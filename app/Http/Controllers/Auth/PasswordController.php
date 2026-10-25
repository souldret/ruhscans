<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Auth\Concerns\ResolvesAuthenticatedUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function update(UpdatePasswordRequest $request): RedirectResponse
    {
        $this->authenticatedUser($request)->update([
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        return redirect()
            ->route('account.show')
            ->with('status', 'password-updated');
    }
}
