<?php

namespace App\Livewire;

use App\Actions\Profile\UpdateReadingPreferences;
use App\Models\Tag;
use App\Rules\SafePanicUrl;
use App\Services\Ops\SiteOperations;
use App\Services\Seo\MetaBuilder;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportPageComponents\PageComponentConfig;

class ReadingPreferences extends Component
{
    public bool $kapak_blur = true;

    public bool $gizli_mod = false;

    public string $panik_url = 'https://www.google.com';

    /** @var list<string> */
    public array $etiket_karaliste = [];

    public function mount(SiteOperations $ops): void
    {
        $user = auth()->user();
        $this->kapak_blur = (bool) $user->kapak_blur;
        $this->gizli_mod = (bool) $user->gizli_mod;
        $this->panik_url = $user->panik_url ?: $ops->panicUrl();
        $this->etiket_karaliste = array_values($user->etiket_karaliste ?? []);
    }

    public function save(UpdateReadingPreferences $action): void
    {
        $validated = $this->validate([
            'kapak_blur' => ['boolean'],
            'gizli_mod' => ['boolean'],
            'panik_url' => ['required', 'string', 'max:255', new SafePanicUrl],
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

    public function render(MetaBuilder $meta): View|PageComponentConfig
    {
        return view('livewire.reading-preferences', [
            'tags' => Tag::query()->orderBy('name')->get(),
        ])->layout('layouts.night', [
            'seo' => $meta->privatePage(
                'Okuma tercihleri',
                'Kapak bulanıklığı, gizli mod ve panik adresi. 18+ hesap ayarı; dizine eklenmez.',
            ),
        ]);
    }
}
