<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class CoverBlurToggle extends Component
{
    public bool $blur = true;

    public function mount(): void
    {
        $this->blur = (bool) (auth()->user()?->kapak_blur ?? true);
    }

    public function setBlur(bool $blur): void
    {
        $user = auth()->user();

        if ($user && $user->kapak_blur !== $blur) {
            $user->forceFill(['kapak_blur' => $blur])->save();
        }

        $this->blur = $blur;
    }

    public function render(): View
    {
        return view('livewire.cover-blur-toggle');
    }
}
