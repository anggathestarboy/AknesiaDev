<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
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
    }

    public function down(): void
    {
        DB::table('asset_containers')->whereIn('handle', ['assets', 'private_library'])->delete();
    }
};
