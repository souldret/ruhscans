<?php

namespace App\Livewire\Profile;

use App\Actions\Profile\UpdateReadingPreferences;
use App\Models\Tag;
use App\Rules\SafePanicUrl;
use App\Services\Ops\SiteOperations;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class PrivacySettings extends Component
{
    public bool $kapak_blur = true;

    public bool $gizli_mod = false;

    public bool $notr_sekme_baslik = false;

    public string $panik_url = 'https://www.google.com';

    /** @var list<string> */
    public array $etiket_karaliste = [];

    public string $tagSearch = '';

    public string $statusMessage = '';

    public function mount(SiteOperations $ops): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $this->kapak_blur = (bool) $user->kapak_blur;
        $this->gizli_mod = (bool) $user->gizli_mod;
        $this->notr_sekme_baslik = (bool) $user->notr_sekme_baslik;
        $this->panik_url = $user->panik_url ?: $ops->panicUrl();
        $this->etiket_karaliste = array_values($user->etiket_karaliste ?? []);
    }

    public function updatedKapakBlur(): void
    {
        $this->persistInstant('Kapak bulanıklığı güncellendi.');
    }

    public function updatedGizliMod(): void
    {
        $this->persistInstant('Gizli mod güncellendi.');
    }

    public function updatedNotrSekmeBaslik(): void
    {
        $this->persistInstant('Sekme başlığı tercihi güncellendi.');
    }

    public function toggleTag(string $slug): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        if (in_array($slug, $this->etiket_karaliste, true)) {
            $this->etiket_karaliste = array_values(array_diff($this->etiket_karaliste, [$slug]));
        } else {
            if (! Tag::query()->where('slug', $slug)->exists()) {
                return;
            }
            $this->etiket_karaliste[] = $slug;
        }

        $this->persistInstant('Etiket kara listesi güncellendi.');
    }

    public function savePanicUrl(UpdateReadingPreferences $action): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'privacy-panic|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 20)) {
            $this->addError('panik_url', 'Çok fazla istek. Lütfen kısa bir süre bekleyin.');

            return;
        }
        RateLimiter::hit($key, 60);

        $validated = $this->validate([
            'panik_url' => ['required', 'string', 'max:255', new SafePanicUrl],
        ], [
            'panik_url.required' => 'Panik hedef adresi boş bırakılamaz.',
            'panik_url.max' => 'Panik hedef adresi en fazla 255 karakter olabilir.',
        ]);

        $updated = $action->handle($user, [
            'kapak_blur' => $this->kapak_blur,
            'gizli_mod' => $this->gizli_mod,
            'notr_sekme_baslik' => $this->notr_sekme_baslik,
            'panik_url' => trim($validated['panik_url']),
            'etiket_karaliste' => $this->etiket_karaliste,
        ]);

        $this->panik_url = $updated->panik_url;
        $this->statusMessage = 'Panik hedef adresi kaydedildi.';
        $this->emitPrefs($updated);
        $this->dispatch('toast', message: 'Panik hedef adresi kaydedildi.');
    }

    private function persistInstant(string $message): void
    {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        $key = 'privacy-toggle|'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 60)) {
            $this->dispatch('toast', message: 'Çok hızlı işlem yapıyorsun, lütfen biraz bekle.');

            return;
        }
        RateLimiter::hit($key, 60);

        $action = app(UpdateReadingPreferences::class);
        $updated = $action->handle($user, [
            'kapak_blur' => $this->kapak_blur,
            'gizli_mod' => $this->gizli_mod,
            'notr_sekme_baslik' => $this->notr_sekme_baslik,
            'panik_url' => $user->panik_url ?: app(SiteOperations::class)->panicUrl(),
            'etiket_karaliste' => $this->etiket_karaliste,
        ]);

        $this->etiket_karaliste = array_values($updated->etiket_karaliste ?? []);
        $this->statusMessage = $message;
        $this->emitPrefs($updated);
        $this->dispatch('toast', message: $message);
    }

    private function emitPrefs($user): void
    {
        $this->dispatch(
            'prefs-updated',
            blur: (bool) $user->kapak_blur,
            stealth: (bool) $user->gizli_mod,
            neutralTitle: (bool) $user->notr_sekme_baslik,
            panic: $user->panik_url,
        );
    }

    public function render(): View
    {
        $search = trim($this->tagSearch);

        $tags = Tag::query()
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->limit(40)
            ->get();

        $selectedTags = empty($this->etiket_karaliste)
            ? collect()
            : Tag::query()->whereIn('slug', $this->etiket_karaliste)->orderBy('name')->get();

        return view('livewire.profile.privacy-settings', [
            'tags' => $tags,
            'selectedTags' => $selectedTags,
        ]);
    }
}
