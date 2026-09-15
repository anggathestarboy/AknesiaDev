<?php

use App\Modules\Collections\Http\Controllers\PageController;
use App\Modules\Forms\Http\Controllers\FormSubmissionController;
use App\Modules\Sitemap\Http\Controllers\SitemapController;
use App\Modules\SiteSettings\Http\Controllers\SiteSettingsController;
use Illuminate\Support\Facades\Route;

if (config('cms.enabled')) {
    Route::prefix(config('cms.route'))->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard'))->name('cms.home');

        Route::middleware(['auth', 'verified', 'permission:access cp'])->group(function () {
            Route::view('dashboard', 'dashboard')->name('dashboard');
        });
    });
}

require __DIR__.'/settings.php';

// Global (not per-site) robots.txt/llms.txt — see SiteSettingsController.
// Registered ahead of the catch-all page route below, since its "{uri}"
// wildcard would otherwise swallow these paths too.
Route::get('/robots.txt', [SiteSettingsController::class, 'robots'])->name('robots');
Route::get('/llms.txt', [SiteSettingsController::class, 'llms'])->name('llms');

// Public sitemap.xml — see SitemapController and SitemapSyncer.
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

// Public form submission endpoint — the form's own markup is hand-built by
// the site's templates (see App\Modules\Forms\Models\Form::fields()), this
// just validates and stores the POST. Named "forms.submit" (not
// "forms.*", which is reserved for the admin module's own route group).
Route::post('/!/forms/{form:handle}', [FormSubmissionController::class, 'store'])->name('forms.submit');

// Public-facing entry pages, served through the collection route/template
// system — see PageController. Named "home" so the auth layouts' logo link
// (route('home')) still resolves.
Route::get('/', [PageController::class, 'show'])->name('home');

Route::get('/{uri}', [PageController::class, 'show'])
    ->where('uri', '.*')
    ->name('page.show');
