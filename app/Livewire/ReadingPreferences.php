<?php

namespace App\Livewire;

use App\Actions\Profile\UpdateReadingPreferences;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.night')]
class ReadingPreferences extends Component
{
    public bool $kapak_blur = true;

    public bool $gizli_mod = false;

    public string $panik_url = 'https://www.google.com';

    /** @var list<string> */
    public array $etiket_karaliste = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->kapak_blur = (bool) $user->kapak_blur;
        $this->gizli_mod = (bool) $user->gizli_mod;
        $this->panik_url = $user->panik_url ?: 'https://www.google.com';
        $this->etiket_karaliste = array_values($user->etiket_karaliste ?? []);
    }

    public function save(UpdateReadingPreferences $action): void
    {
        $validated = $this->validate([
            'kapak_blur' => ['boolean'],
            'gizli_mod' => ['boolean'],
            'panik_url' => ['required', 'url', 'max:255'],
            'etiket_karaliste' => ['array'],
            'etiket_karaliste.*' => ['string', 'max:80'],
        ]);

        $user = $action->handle(auth()->user(), [
            'kapak_blur' => (bool) $validated['kapak_blur'],
            'gizli_mod' => (bool) $validated['gizli_mod'],
            'panik_url' => $validated['panik_url'],
            'etiket_karaliste' => array_values($validated['etiket_karaliste']),
        ]);

        $this->etiket_karaliste = array_values($user->etiket_karaliste ?? []);
        $this->dispatch('prefs-updated', blur: $user->kapak_blur, stealth: $user->gizli_mod, panic: $user->panik_url);
        $this->dispatch('toast', message: 'Okuma tercihleri kaydedildi.');
    }

    public function render(): View
    {
        return view('livewire.reading-preferences', [
            'tags' => Tag::query()->orderBy('name')->get(),
        ]);
    }
}
