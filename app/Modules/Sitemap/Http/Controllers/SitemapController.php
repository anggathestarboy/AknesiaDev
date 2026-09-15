<?php

namespace App\Modules\Sitemap\Http\Controllers;

use App\Modules\Sitemap\Models\Sitemap;
use Illuminate\Http\Response;

/**
 * Serves /sitemap.xml — public, unauthenticated — from the sitemaps table
 * kept in sync by SitemapSyncer (see Entry::booted()).
 */
class SitemapController
{
    public function index(): Response
    {
        $urls = Sitemap::where('is_active', true)->orderBy('url')->get();

        return response()
            ->view('sitemap::sitemap', ['urls' => $urls])
            ->header('Content-Type', 'text/xml');
    }
}
