<?php

namespace App\Modules\Forms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Form builder and submissions.
 * Owns tables: forms, form_submissions.
 */
class FormsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'forms');

        // Lets <livewire:forms::public-form /> in any site template resolve
        // to this module's own Livewire classes — same convention as
        // MediaServiceProvider's 'media' namespace for @livewire("media::asset-picker").
        Livewire::addNamespace('forms', classNamespace: 'App\\Modules\\Forms\\Livewire');

        Route::middleware(['web', 'auth', 'verified', 'permission:access cp'])
            ->prefix(config('cms.route'))
            ->name('forms.')
            ->group(__DIR__.'/routes.php');
    }
}
