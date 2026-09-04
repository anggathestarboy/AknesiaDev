<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('taxonomy_terms', function (Blueprint $table) {
            $table->unsignedBigInteger('origin_id')->nullable()->after('site')->comment('Self-ref for localization (nullable)');
            $table->string('blueprint', 30)->nullable()->after('taxonomy');

            $table->index('origin_id', 'taxonomy_terms_origin_id_index');
            $table->foreign('origin_id', 'taxonomy_terms_origin_id_foreign')
                ->references('id')->on('taxonomy_terms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('taxonomy_terms', function (Blueprint $table) {
            $table->dropForeign('taxonomy_terms_origin_id_foreign');
            $table->dropColumn(['origin_id', 'blueprint']);
        });
    }
};
