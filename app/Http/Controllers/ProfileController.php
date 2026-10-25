<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\Concerns\ResolvesAuthenticatedUser;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use ResolvesAuthenticatedUser;

    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $this->authenticatedUser($request),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(DeleteAccountRequest $request): RedirectResponse
    {
        $user = $this->authenticatedUser($request);

        Auth::logout();

        app(AuditLogger::class)->write('user.deleted', $user, 'Üye kendi hesabını sildi.', [], $user);

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
