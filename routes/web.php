<?php

use App\Modules\Collections\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

if (config('cms.enabled')) {
    Route::prefix(config('cms.route'))->group(function () {
        Route::get('/', fn () => redirect()->route('dashboard'))->name('cms.home');

        Route::middleware(['auth', 'verified'])->group(function () {
            Route::view('dashboard', 'dashboard')->name('dashboard');
        });
    });
}

require __DIR__.'/settings.php';

// Public-facing entry pages, served through the collection route/template
// system — see PageController. Named "home" so the auth layouts' logo link
// (route('home')) still resolves.
Route::get('/', [PageController::class, 'show'])->name('home');

Route::get('/{uri}', [PageController::class, 'show'])
    ->where('uri', '.*')
    ->name('page.show');
