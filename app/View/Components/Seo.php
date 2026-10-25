<?php

namespace App\View\Components;

use App\Support\AdultGate;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Seo extends Component
{
    public function __construct(
        public string $title,
        public string $description,
        public ?string $image = null,
        public string $type = 'website',
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.seo', [
            'canonical' => url()->current(),
            'image' => $this->image ?: asset('images/banner.svg'),
            'adult' => AdultGate::confirmed(request()),
        ]);
    }
}
