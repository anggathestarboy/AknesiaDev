<?php

// Routes for the Package Updates module. Registered by
// PackageUpdatesServiceProvider under the CMS prefix (config('cms.route')),
// 'web' + 'auth' + 'verified' + 'permission:access cp' middleware, and the
// 'package-updates.' route name prefix. Do NOT add ->prefix()/->middleware()/->name() again in this file.

use App\Modules\PackageUpdates\Livewire\PackageUpdatesManager;
use Illuminate\Support\Facades\Route;

Route::get('package-updates', PackageUpdatesManager::class)->name('index');
