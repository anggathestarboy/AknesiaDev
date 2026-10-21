<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Opt-in switch for the activity log: draft:setup leaves it off (see
 * config/activity-log.php), this turns ACTIVITY_LOG_ENABLED on in .env and
 * migrates its tables (kept out of database/migrations so draft:setup never creates them).
 */
class DraftActivityLogCommand extends Command
{
    protected $signature = 'draft:activity_log';

    protected $description = 'Enable the activity log (sets ACTIVITY_LOG_ENABLED=true and migrates its tables)';

    public function handle(): int
    {
        $envPath = $this->laravel->environmentFilePath();

        if (! is_file($envPath)) {
            $this->error('No .env file found — create one first (cp .env.example .env).');

            return self::FAILURE;
        }

        $env = file_get_contents($envPath);
        $env = preg_match('/^ACTIVITY_LOG_ENABLED=.*$/m', $env)
            ? preg_replace('/^ACTIVITY_LOG_ENABLED=.*$/m', 'ACTIVITY_LOG_ENABLED=true', $env)
            : rtrim($env)."\nACTIVITY_LOG_ENABLED=true\n";
        file_put_contents($envPath, $env);

        $this->call('migrate', ['--force' => true, '--path' => 'app/Modules/ActivityLog/database/migrations']);
        $this->call('config:clear');

        $this->info('Activity log enabled.');

        return self::SUCCESS;
    }
}
