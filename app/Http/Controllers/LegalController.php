<?php

namespace App\Http\Controllers;

use App\Http\Requests\TakedownRequest;
use Illuminate\Http\RedirectResponse;

class LegalController extends Controller
{
    public function takedown(TakedownRequest $request): RedirectResponse
    {
        return back()->with('sent', true);
    }
}
