<?php

namespace App\Modules\Blueprints;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Blueprint and fieldset definitions (the field-schema system used by other modules).
 * Owns tables: blueprints, fieldsets.
 */
class BlueprintsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'blueprints');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web', 'auth', 'verified', 'permission:access cp'])
            ->prefix(config('cms.route'))
            ->name('blueprints.')
            ->group(__DIR__.'/routes.php');
    }
}
