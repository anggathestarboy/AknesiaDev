<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Bootstraps a brand-new install end to end by chaining the small, single-
 * purpose commands each module already owns: the first Super Admin user
 * (Users), a default site (Sites), the default "Pages" collection with its
 * "Homepage" blueprint and a dummy homepage entry (Collections/Blueprints),
 * and the default admin sidebar menu (Navigation) — whose own "Pages" link
 * assumes that collection exists. Lives outside every module since it
 * orchestrates across all of them rather than belonging to one. Meant to be
 * run once against an otherwise-empty database — e.g. right after
 * `php artisan migrate:fresh` — not as a way to wipe existing data itself.
 */
class DraftSetupCommand extends Command
{
    protected $signature = 'draft:setup
        {--name= : Admin name}
        {--email= : Admin email}
        {--password= : Admin password (min 8 characters)}
        {--site-name= : Default site name}
        {--site-handle= : Default site handle}
        {--force : Create another admin even if users already exist}';

    protected $description = 'Bootstrap a fresh install: first Super Admin user, default site, and default admin sidebar menu';

    public function handle(): int
    {
        $this->info('Setting up Draft CMS...');
        $this->newLine();

        $exitCode = $this->call('users:create-super-admin', array_filter([
            '--name' => $this->option('name'),
            '--email' => $this->option('email'),
            '--password' => $this->option('password'),
            '--force' => $this->option('force'),
        ]));

        if ($exitCode !== self::SUCCESS) {
            return $exitCode;
        }

        $this->call('sites:create-default', array_filter([
            '--name' => $this->option('site-name'),
            '--handle' => $this->option('site-handle'),
        ]));

        $this->call('collections:create-pages');
        $this->call('blueprints:create-homepage');
        $this->call('collections:create-homepage-entry');

        $this->call('admin:sidebar');

        $this->newLine();
        $this->info('Setup complete. Log in with the admin account you just created.');

        return self::SUCCESS;
    }
}
