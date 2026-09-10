<?php

namespace App\Modules\SiteSettings\Models;

use App\Modules\SiteSettings\Services\SchemaMarkupBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * A single global row (see current()) holding site-wide, not-per-site
 * configuration: robots.txt, llms.txt, and the schema.org JSON-LD (see
 * SchemaMarkupBuilder) injected into every page's <head>.
 *
 * @property int $id
 * @property string|null $robots_txt
 * @property string|null $llms_txt
 * @property array|null $llms_config
 * @property array|null $schema
 */
class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = ['robots_txt', 'llms_txt', 'llms_config', 'schema'];

    protected $casts = [
        'llms_config' => 'array',
        'schema' => 'array',
    ];

    /**
     * The single settings row, creating it (empty) on first use.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }

    /**
     * This site's schema.org JSON-LD blocks (one per entry in the Schema
     * tab — an admin can add more than one, e.g. Organization + WebSite),
     * skipping any block without a name filled in yet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function structuredDataBlocks(): array
    {
        return app(SchemaMarkupBuilder::class)->buildAll($this->schemaBlocks());
    }

    /**
     * The raw schema blocks as stored, always as a list — transparently
     * upgrading the old single-block format (a flat array with a
     * top-level "type"/"name" key, from before multiple blocks were
     * supported) into a one-item list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function schemaBlocks(): array
    {
        $schema = $this->schema ?? [];

        if (array_is_list($schema)) {
            return $schema;
        }

        return isset($schema['type']) || isset($schema['name']) ? [$schema] : [];
    }
}
