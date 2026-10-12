<?php

namespace App\Modules\PackageUpdates;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Admin page listing available updates for the app's direct Composer and
 * npm dependencies (major/minor/patch). Owns no tables — results are cached,
 * not persisted, see PackageUpdateChecker.
 */
class PackageUpdatesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'package-updates');

        Route::middleware(['web', 'auth', 'verified', 'permission:access cp'])
            ->prefix(config('cms.route'))
            ->name('package-updates.')
            ->group(__DIR__.'/routes.php');
    }
}
