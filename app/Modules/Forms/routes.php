<?php

// Routes for the Forms module.
// Registered by FormsServiceProvider under the CMS prefix (config('cms.route')),
// 'web' + 'auth' + 'verified' middleware, and the 'forms.' route name prefix.
// Do NOT add ->prefix()/->middleware()/->name() again in this file.

use App\Modules\Forms\Livewire\FormManager;
use App\Modules\Forms\Livewire\FormSubmissionManager;
use Illuminate\Support\Facades\Route;

Route::get('forms', FormManager::class)->name('index');
Route::get('forms/{form:handle}/submissions', FormSubmissionManager::class)->name('submissions');
