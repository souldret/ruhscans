<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $body = <<<'TXT'
        User-agent: *
        Allow: /
        Disallow: /yonetim
        Disallow: /hesap
        Disallow: /indir

        Sitemap: SITEMAP
        TXT;

        $body = str_replace('SITEMAP', route('sitemap'), $body);
        $stripped = preg_replace('/^ +/m', '', $body);

        return response(trim(is_string($stripped) ? $stripped : $body)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
