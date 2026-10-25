<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Response;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $sitemap = Sitemap::create()
            ->add(Url::create('/'))
            ->add(Url::create('/oyunlar'))
            ->add(Url::create('/coin-paketleri'))
            ->add(Url::create('/kullanim-sartlari'))
            ->add(Url::create('/gizlilik'))
            ->add(Url::create('/cerez-politikasi'))
            ->add(Url::create('/icerik-kaldirma'))
            ->add(Url::create('/hakkimizda'));

        Game::query()->published()->orderBy('slug')->each(function (Game $game) use ($sitemap): void {
            $url = Url::create('/oyun/'.$game->slug);

            if ($game->updated_at !== null) {
                $url->setLastModificationDate($game->updated_at);
            }

            $sitemap->add($url);
        });

        return response($sitemap->render(), 200, ['Content-Type' => 'application/xml']);
    }
}
