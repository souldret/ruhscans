<?php

namespace App\Http\Controllers;

use App\Services\CatalogCache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(CatalogCache $cache): View
    {
        return view('home', $cache->home());
    }
}
