<?php

namespace App\Modules\Navigation;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Navigation menus.
 * Owns tables: navigations.
 */
class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'navigation');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web', 'auth', 'verified'])
            ->prefix(config('cms.route'))
            ->name('navigation.')
            ->group(__DIR__.'/routes.php');
    }
}
