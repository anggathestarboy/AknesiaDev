<?php

namespace App\Modules\Collections;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Collection & Entry management (content types and their content).
 * Owns tables: collections, entries.
 */
class CollectionsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'collections');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web', 'auth', 'verified'])
            ->prefix(config('cms.route'))
            ->name('collections.')
            ->group(__DIR__.'/routes.php');
    }
}
