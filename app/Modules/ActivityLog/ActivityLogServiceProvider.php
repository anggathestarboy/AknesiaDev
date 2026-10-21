<?php

namespace App\Modules\ActivityLog;

use App\Modules\ActivityLog\Console\Commands\ProcessActivityLogDeletionsCommand;
use App\Modules\ActivityLog\Listeners\LogAuthenticationEvents;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * Read-only audit trail of admin activity — automatic create/update/delete
 * logging for any model that `use`s Concerns\LogsActivity, plus explicit
 * App\Modules\ActivityLog\Services\ActivityLogger::log() calls for
 * everything else (pivot changes, ban/unban, password resets, login/logout
 * — see LogAuthenticationEvents below).
 * Owns tables: activity_logs.
 */
class ActivityLogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->disableUntilMigrated();

        $this->loadViewsFrom(__DIR__.'/resources/views', 'activity-log');

        // Production creates these tables only via draft:activity_log.
        if ($this->app->runningUnitTests()) {
            $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        }

        $this->commands([
            ProcessActivityLogDeletionsCommand::class,
        ]);

        Event::listen(Login::class, [LogAuthenticationEvents::class, 'handleLogin']);
        Event::listen(Logout::class, [LogAuthenticationEvents::class, 'handleLogout']);
        Event::listen(Failed::class, [LogAuthenticationEvents::class, 'handleFailed']);

        Route::middleware(['web', 'auth', 'verified', 'permission:access cp'])
            ->prefix(config('cms.route'))
            ->name('activity-log.')
            ->group(__DIR__.'/routes.php');
    }

    /**
     * ACTIVITY_LOG_ENABLED can be true before draft:activity_log has created
     * the tables (or against a fresh database) — treat that as off instead
     * of letting every query fail.
     */
    protected function disableUntilMigrated(): void
    {
        if (! config('activity-log.enabled') || $this->app->runningUnitTests()) {
            return;
        }

        try {
            $migrated = Schema::hasTable('activity_logs') && Schema::hasTable('activity_log_deletion_requests');
        } catch (\Throwable) {
            $migrated = false;
        }

        if (! $migrated) {
            config(['activity-log.enabled' => false]);
        }
    }
}
