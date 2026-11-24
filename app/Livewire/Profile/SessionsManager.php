<?php

namespace App\Livewire\Profile;

use App\Services\Profile\SessionManager;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class SessionsManager extends Component
{
    public string $statusMessage = '';

    public function logoutOtherDevices(SessionManager $sessions): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'sessions-revoke-all|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->statusMessage = 'Çok fazla deneme. Lütfen kısa bir süre bekleyin.';

            return;
        }
        RateLimiter::hit($key, 60);

        $count = $sessions->logoutOtherDevices($user, session()->getId());
        $this->statusMessage = $count > 0
            ? "{$count} diğer oturum kapatıldı."
            : 'Kapatılacak başka aktif oturum yok.';

        $this->dispatch('toast', message: $this->statusMessage);
    }

    public function revokeSession(string $sessionId, SessionManager $sessions): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'sessions-revoke-one|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            return;
        }
        RateLimiter::hit($key, 60);

        $revoked = $sessions->revokeOne($user, $sessionId, session()->getId());

        if ($revoked) {
            $this->statusMessage = 'Seçilen oturum kapatıldı.';
            $this->dispatch('toast', message: $this->statusMessage);
        }
    }

    public function render(SessionManager $sessions): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        return view('livewire.profile.sessions-manager', [
            'sessions' => $sessions->listFor($user),
            'currentSessionId' => session()->getId(),
        ]);
    }
}
