<?php

namespace App\Modules\Forms;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

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
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        Route::middleware(['web', 'auth', 'verified'])
            ->prefix(config('cms.route'))
            ->name('forms.')
            ->group(__DIR__.'/routes.php');
    }
}
