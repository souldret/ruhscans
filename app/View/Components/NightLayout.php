<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NightLayout extends Component
{
    /**
     * @param  array<string, mixed>|null  $seo
     */
    public function __construct(public ?string $title = null, public ?array $seo = null) {}

    public function render(): View
    {
        return view('layouts.night');
    }
}
