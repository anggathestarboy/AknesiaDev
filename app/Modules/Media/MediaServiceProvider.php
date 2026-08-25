<?php

namespace App\Modules\Media;

use App\Modules\Media\Console\Commands\MakeMediaCommand;
use App\Modules\Media\Console\Commands\RemoveMediaCommand;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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

        Livewire::addNamespace('media', classNamespace: 'App\\Modules\\Media\\Livewire');

        $this->commands([
            MakeMediaCommand::class,
            RemoveMediaCommand::class,
        ]);

        Route::middleware(['web', 'auth', 'verified'])
            ->prefix(config('cms.route'))
            ->name('media.')
            ->group(__DIR__.'/routes.php');
    }
}
