<?php

use App\Modules\Blueprints\BlueprintsServiceProvider;
use App\Modules\Collections\CollectionsServiceProvider;
use App\Modules\Forms\FormsServiceProvider;
use App\Modules\Globals\GlobalsServiceProvider;
use App\Modules\Media\MediaServiceProvider;
use App\Modules\Navigation\NavigationServiceProvider;
use App\Modules\Redirects\RedirectsServiceProvider;
use App\Modules\Sitemap\SitemapServiceProvider;
use App\Modules\Sites\SitesServiceProvider;
use App\Modules\Structure\StructureServiceProvider;
use App\Modules\Taxonomies\TaxonomiesServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,

    // CMS modules (app/Modules/*) — see docs/AI-CONTEXT.md "Sistem modul CMS".
    CollectionsServiceProvider::class,
    TaxonomiesServiceProvider::class,
    BlueprintsServiceProvider::class,
    MediaServiceProvider::class,
    GlobalsServiceProvider::class,
    FormsServiceProvider::class,
    NavigationServiceProvider::class,
    StructureServiceProvider::class,
    SitesServiceProvider::class,
    SitemapServiceProvider::class,
    RedirectsServiceProvider::class,
];
