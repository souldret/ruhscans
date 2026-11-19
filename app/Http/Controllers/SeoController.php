<?php

namespace App\Http\Controllers;

use App\Services\Seo\SitemapBuilder;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /horizon',
            'Disallow: /dashboard',
            'Disallow: /profile',
            'Disallow: /cuzdan',
            'Disallow: /kutuphane',
            'Disallow: /okuma-gecmisi',
            'Disallow: /okuma-tercihleri',
            'Disallow: /sayfa/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    public function index(SitemapBuilder $sitemap): Response
    {
        $pages = $sitemap->pageCount();

        if ($pages === 1) {
            return $this->xml('seo.sitemap-urlset', [
                'static' => $sitemap->staticUrls(),
                'urls' => $sitemap->page(1),
            ]);
        }

        $maps = [];

        for ($page = 1; $page <= $pages; $page++) {
            $maps[] = route('sitemap.page', $page);
        }

        return $this->xml('seo.sitemap-index', ['maps' => $maps]);
    }

    public function page(int $page, SitemapBuilder $sitemap): Response
    {
        abort_if($page < 1 || $page > $sitemap->pageCount(), 404);

        return $this->xml('seo.sitemap-urlset', [
            'static' => $page === 1 ? $sitemap->staticUrls() : [],
            'urls' => $sitemap->page($page),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function xml(string $view, array $data): Response
    {
        return response()
            ->view($view, $data)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
