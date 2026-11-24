<?php

namespace App\Livewire\Profile;

use App\Services\Profile\AvatarService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class SummaryCard extends Component
{
    use WithFileUploads;

    public $avatar = null;

    public ?int $cropX = null;

    public ?int $cropY = null;

    public ?int $cropSize = null;

    #[On('profile-updated')]
    public function refreshUser(): void
    {
        // Yeniden render tetikler.
    }

    public function saveAvatar(AvatarService $avatars): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'avatar-upload|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('avatar', 'Çok fazla yükleme denemesi. Lütfen bir dakika sonra tekrar dene.');

            return;
        }
        RateLimiter::hit($key, 60);

        $this->validate([
            'avatar' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp'],
        ], [
            'avatar.required' => 'Lütfen bir görsel seçin.',
            'avatar.max' => 'Avatar dosyası en fazla 5 MB olabilir.',
            'avatar.mimes' => 'Yalnızca JPG, PNG veya WebP yüklenebilir.',
        ]);

        $crop = null;
        if ($this->cropSize && $this->cropSize > 0) {
            $crop = [
                'x' => max(0, (int) $this->cropX),
                'y' => max(0, (int) $this->cropY),
                'width' => (int) $this->cropSize,
                'height' => (int) $this->cropSize,
            ];
        }

        try {
            $avatars->upload($user, $this->avatar, $crop);
        } catch (\RuntimeException $e) {
            $this->addError('avatar', $e->getMessage());

            return;
        }

        $this->reset(['avatar', 'cropX', 'cropY', 'cropSize']);
        $this->dispatch('toast', message: 'Avatar güncellendi.');
    }

    public function removeAvatar(AvatarService $avatars): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $avatars->remove($user);
        $this->dispatch('toast', message: 'Avatar kaldırıldı.');
    }

    public function render(): View
    {
        $user = auth()->user()->fresh();

        return view('livewire.profile.summary-card', [
            'user' => $user,
            'vipDays' => $user->vipRemainingDays(),
            'initial' => mb_strtoupper(mb_substr($user->name, 0, 1)),
        ]);
    }
}
