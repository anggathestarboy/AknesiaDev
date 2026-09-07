<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

if (config('cms.enabled')) {
    Route::prefix(config('cms.route'))->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard'))->name('cms.home');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::view('dashboard', 'dashboard')->name('dashboard');
        });
    });
}

require __DIR__.'/settings.php';
