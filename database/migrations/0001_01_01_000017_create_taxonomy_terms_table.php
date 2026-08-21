<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomy_terms', function (Blueprint $table) {
            $table->id();
            $table->string('site');
            $table->string('slug');
            $table->string('uri')->nullable();
            $table->string('taxonomy');
            $table->json('data');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['slug', 'taxonomy', 'site'], 'taxonomy_terms_slug_taxonomy_site_unique');
            $table->index('site', 'taxonomy_terms_site_index');
            $table->index('uri', 'taxonomy_terms_uri_index');
            $table->index('taxonomy', 'taxonomy_terms_taxonomy_index');

            $table->foreign('taxonomy', 'taxonomy_terms_taxonomy_foreign')
                ->references('handle')->on('taxonomies');
        });
    }

    public function down(): void
    {
        Schema::table('taxonomy_terms', function (Blueprint $table) {
            $table->dropForeign('taxonomy_terms_taxonomy_foreign');
        });
        Schema::dropIfExists('taxonomy_terms');
    }
};
