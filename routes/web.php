<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

if (config('cms.enabled')) {
    Route::middleware(['auth', 'verified'])
        ->prefix(config('cms.route'))
        ->group(function () {
            Route::view('dashboard', 'dashboard')->name('dashboard');
        });
}

require __DIR__.'/settings.php';
