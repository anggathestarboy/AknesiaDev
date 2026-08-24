<?php

// Routes for the Blueprints module.
// Registered by BlueprintsServiceProvider under the CMS prefix (config('cms.route')),
// 'web' + 'auth' + 'verified' middleware, and the 'blueprints.' route name prefix.
// Do NOT add ->prefix()/->middleware()/->name() again in this file.

use App\Modules\Blueprints\Livewire\BlueprintManager;
use App\Modules\Blueprints\Livewire\FieldsetManager;
use Illuminate\Support\Facades\Route;

Route::get('blueprints', BlueprintManager::class)->name('index');
Route::get('fieldsets', FieldsetManager::class)->name('fieldsets');
