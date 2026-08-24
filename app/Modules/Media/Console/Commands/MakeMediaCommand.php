<?php

namespace App\Modules\Media\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MakeMediaCommand extends Command
{
    protected $signature = 'make:media';

    protected $description = 'Seed the default Media Library asset containers (assets, private_library)';

    public function handle(): int
    {
        DB::table('asset_containers')->insertOrIgnore([
            [
                'handle' => 'assets',
                'title' => 'Assets',
                'disk' => 'public',
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'handle' => 'private_library',
                'title' => 'Private Library',
                'disk' => 'local',
                'settings' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->info('Media containers seeded: assets, private_library.');

        return self::SUCCESS;
    }
}
