<?php

namespace App\Modules\Media;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Asset containers and file/media metadata.
 * Owns tables: asset_containers, assets_meta.
 */
class MediaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'media');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web', 'auth', 'verified'])
            ->prefix(config('cms.route'))
            ->name('media.')
            ->group(__DIR__.'/routes.php');
    }
}
