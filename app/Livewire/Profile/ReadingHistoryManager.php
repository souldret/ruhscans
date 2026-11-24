<?php

namespace App\Livewire\Profile;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ReadingHistoryManager extends Component
{
    public string $statusMessage = '';

    public function removeEntry(int $entryId): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'history-remove|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            return;
        }
        RateLimiter::hit($key, 60);

        $entry = $user->libraryEntries()->whereKey($entryId)->first();

        if (! $entry) {
            return;
        }

        $entry->forceFill([
            'last_chapter_id' => null,
            'last_page_order' => null,
            'completed' => false,
        ])->save();

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->log('Okuma geçmişinden bir kayıt silindi.');

        $this->statusMessage = 'Kayıt okuma geçmişinden kaldırıldı.';
        $this->dispatch('toast', message: $this->statusMessage);
    }

    public function clearAll(): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'history-clear|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            return;
        }
        RateLimiter::hit($key, 60);

        $affected = $user->libraryEntries()
            ->whereNotNull('last_chapter_id')
            ->update([
                'last_chapter_id' => null,
                'last_page_order' => null,
                'completed' => false,
            ]);

        activity('profile')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['cleared_count' => $affected])
            ->log('Tüm okuma geçmişi temizlendi.');

        $this->statusMessage = $affected > 0
            ? 'Tüm okuma geçmişin temizlendi.'
            : 'Temizlenecek okuma geçmişi yok.';

        $this->dispatch('toast', message: $this->statusMessage);
    }

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $entries = $user->libraryEntries()
            ->whereNotNull('last_chapter_id')
            ->with(['series', 'lastChapter'])
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get();

        return view('livewire.profile.reading-history-manager', [
            'entries' => $entries,
        ]);
    }
}
