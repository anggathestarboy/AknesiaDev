<?php

namespace App\Modules\Collections\Models;

use App\Models\TaxonomyTerm;
use App\Models\User;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Services\EntryUrlResolver;
use App\Modules\Media\Models\AssetMeta;
use App\Modules\Sitemap\Services\SitemapSyncer;
use App\Modules\SiteSettings\Services\SchemaMarkupBuilder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Fluent;

/**
 * @property string $id
 * @property string $site
 * @property string|null $origin_id
 * @property bool $published
 * @property string $status
 * @property string|null $slug
 * @property string|null $uri
 * @property string|null $date
 * @property int|null $order
 * @property string $collection
 * @property string|null $blueprint
 * @property array $data
 */
class Entry extends Model
{
    use HasUuids;

    protected $table = 'entries';

    protected $fillable = [
        'site', 'origin_id', 'published', 'status', 'slug', 'uri', 'date', 'order', 'collection', 'blueprint', 'data', 'schema', 'seo',
    ];

    protected $casts = [
        'published' => 'boolean',
        'data' => 'array',
        'schema' => 'array',
        'seo' => 'array',
    ];

    /**
     * Keeps the sitemaps table in sync with every entry save/delete — see
     * SitemapSyncer. Runs on every save regardless of which admin screen
     * (or test) made it, rather than only from EntryForm::save(), so
     * sitemap.xml never drifts from what's actually published.
     */
    protected static function booted(): void
    {
        static::saved(fn (self $entry) => app(SitemapSyncer::class)->sync($entry));
        static::deleted(fn (self $entry) => app(SitemapSyncer::class)->remove($entry));
    }

    /**
     * Every "SEO" tab field key with its default value — a freshly-enabled
     * entry (or one predating the tab) still returns every key. Robots
     * defaults to noindex+nofollow, mirroring the "if left blank" default
     * an editor would otherwise have to set by hand on every entry.
     *
     * @return array<string, mixed>
     */
    public static function defaultSeoFields(): array
    {
        return [
            'meta_title' => '',
            'meta_description' => '',
            'meta_canonical' => '',
            'robots' => ['noindex', 'nofollow'],
            'meta_featured_image' => [],
            'meta_author' => '',
            'sitemap_priority' => '0.8',
            'sitemap_changefreq' => 'weekly',
        ];
    }

    /**
     * This entry's own SEO fields (see the collection's "SEO" tab toggle),
     * merged over the defaults so a partially-filled or pre-toggle entry
     * still returns every key.
     *
     * @return array<string, mixed>
     */
    public function seoFields(): array
    {
        return array_merge(static::defaultSeoFields(), $this->seo ?? []);
    }

    /**
     * This entry's own schema.org JSON-LD blocks (see the collection's
     * "Schema" tab toggle) — rendered alongside, not instead of, the
     * site-wide blocks from SiteSetting.
     *
     * @return array<int, array<string, mixed>>
     */
    public function structuredDataBlocks(): array
    {
        return app(SchemaMarkupBuilder::class)->buildAll($this->schemaBlocks());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function schemaBlocks(): array
    {
        return $this->schema ?? [];
    }

    /**
     * This entry's field data for public templates — every associative
     * array (including nested replicator rows) becomes a Fluent instance,
     * so a template can read a field as $data->title as well as
     * $data['title'], at any nesting depth, without wrapping it itself. A
     * "taxonomy" field's stored term ids are also resolved to the actual
     * terms (each with its own data as $data->fieldHandle->title, etc.) —
     * a single term directly for a single-select field, a list of terms
     * for a multi-select one — instead of leaving raw ids for the template
     * to look up itself. A "link" field pointing at an entry (stored as
     * "entry::{id}"), at any nesting depth inside a replicator, is resolved
     * to that entry's public URL, so a template can use it directly as an
     * <a href> instead of the raw "entry::..." marker. An "author" field's
     * stored user id is resolved to that user's name/email (never the
     * password hash or other sensitive columns), so a template can use
     * $data->fieldHandle->name directly. An "assets" field's stored asset
     * ids are resolved to the actual AssetMeta model(s) — one directly for
     * a single-file field (max_items = 1), a list for anything else — so a
     * template can use $data->fieldHandle->url directly instead of looking
     * the asset up itself. A non-localizable field (see
     * overlayNonLocalizableData()) always reflects the origin locale's
     * current value, even on a translation whose own stored value is stale
     * or was never filled in — a template never has to null-guard it. A
     * "replicator" row switched off in the editor (see
     * filterEnabledReplicatorRows()) is dropped entirely, at any nesting
     * depth, instead of being handed to the template.
     */
    public function fluentData(): Fluent
    {
        $fields = $this->blueprintFields();

        $data = $this->overlayNonLocalizableData($this->data ?? [], $fields);
        $data = $this->filterEnabledReplicatorRows($data, $fields);
        $data = $this->resolveTaxonomyFields($data);
        $data = $this->resolveAuthorFields($data);
        $data = $this->resolveLinkFields($data, $fields);
        $data = $this->resolveAssetFields($data, $fields);

        return static::wrapFluent($data);
    }

    /**
     * Drops every "replicator" row whose "_enabled" toggle was switched off
     * in the editor (default true, for a row created before the toggle
     * existed) — a template only ever sees the enabled rows, in their
     * original order, and never has to check the flag itself. Recurses into
     * each surviving row's own nested replicator fields, and strips the
     * now-redundant "_enabled" key from what the template receives.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected function filterEnabledReplicatorRows(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if ($field['type'] !== 'replicator') {
                continue;
            }

            $data[$field['handle']] = collect($data[$field['handle']] ?? [])
                ->filter(fn (array $row): bool => $row['_enabled'] ?? true)
                ->map(function (array $row) use ($field): array {
                    $row = $this->filterEnabledReplicatorRows($row, $field['replicator_fields'] ?? []);
                    unset($row['_enabled']);

                    return $row;
                })
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * Overwrites this entry's own value for every non-localizable field
     * ("Locale" switched off on the blueprint field — editable only from
     * the origin locale) with the origin entry's CURRENT value, live on
     * every read. Mirrors EntryForm::save()'s own copy-from-origin step,
     * but that one only runs when a translation is itself saved — a
     * translation nobody has re-saved since the origin's shared field last
     * changed would otherwise keep showing a stale (or, if never filled in,
     * empty) value on its public page. An origin entry (no origin_id) has
     * nothing to overlay from and is returned unchanged.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected function overlayNonLocalizableData(array $data, array $fields): array
    {
        $origin = $this->origin_id ? static::find($this->origin_id) : null;

        if (! $origin) {
            return $data;
        }

        $originData = $origin->data ?? [];

        foreach ($fields as $field) {
            if ($field['type'] === 'replicator' || ($field['localizable'] ?? false)) {
                continue;
            }

            $data[$field['handle']] = $originData[$field['handle']] ?? ($data[$field['handle']] ?? null);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveAuthorFields(array $data): array
    {
        $authorFields = collect($this->blueprintFields())->where('type', 'author');

        foreach ($authorFields as $field) {
            $handle = $field['handle'];

            if (empty($data[$handle])) {
                continue;
            }

            $author = User::find($data[$handle]);

            $data[$handle] = $author ? ['id' => $author->id, 'name' => $author->name, 'email' => $author->email] : null;
        }

        return $data;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function blueprintFields(): array
    {
        return Blueprint::where('handle', $this->blueprint)->value('data')['fields'] ?? [];
    }

    /**
     * Recursively resolves every "link" field in $fields against $data,
     * walking into a "replicator" field's own rows (each row matching
     * "replicator_fields") to any depth.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected function resolveLinkFields(array $data, array $fields): array
    {
        $resolver = app(EntryUrlResolver::class);

        foreach ($fields as $field) {
            $handle = $field['handle'];

            if ($field['type'] === 'replicator') {
                $data[$handle] = collect($data[$handle] ?? [])
                    ->map(fn (array $row): array => $this->resolveLinkFields($row, $field['replicator_fields'] ?? []))
                    ->all();

                continue;
            }

            if ($field['type'] !== 'link') {
                continue;
            }

            $value = $data[$handle] ?? null;

            if (! is_string($value) || ! str_starts_with($value, 'entry::')) {
                continue;
            }

            $linkedEntry = static::find(substr($value, 7));
            $collection = $linkedEntry ? Collection::where('handle', $linkedEntry->collection)->first() : null;

            $data[$handle] = ($linkedEntry && $collection) ? $resolver->url($collection, $linkedEntry) : null;
        }

        return $data;
    }

    /**
     * Recursively resolves every "assets" field in $fields against $data,
     * walking into a "replicator" field's own rows to any depth — mirrors
     * resolveLinkFields(). A stale id (its asset deleted from the Media
     * Library) is silently dropped rather than left as a dead reference.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $fields
     * @return array<string, mixed>
     */
    protected function resolveAssetFields(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            $handle = $field['handle'];

            if ($field['type'] === 'replicator') {
                $data[$handle] = collect($data[$handle] ?? [])
                    ->map(fn (array $row): array => $this->resolveAssetFields($row, $field['replicator_fields'] ?? []))
                    ->all();

                continue;
            }

            if ($field['type'] !== 'assets') {
                continue;
            }

            $ids = $data[$handle] ?? [];
            $ids = is_array($ids) ? $ids : [];

            $assets = AssetMeta::whereIn('id', $ids)->get()->sortBy(fn (AssetMeta $asset): int => array_search($asset->id, $ids, true))->values();
            $allowsMultiple = ($field['max_items'] ?? null) === null || $field['max_items'] > 1;

            $data[$handle] = $allowsMultiple ? $assets->all() : $assets->first();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveTaxonomyFields(array $data): array
    {
        $taxonomyFields = collect($this->blueprintFields())->where('type', 'taxonomy');

        foreach ($taxonomyFields as $field) {
            $handle = $field['handle'];

            if (! array_key_exists($handle, $data) || ! is_array($data[$handle])) {
                continue;
            }

            $ids = $data[$handle];
            $terms = TaxonomyTerm::whereIn('id', $ids)->get()->sortBy(fn (TaxonomyTerm $term): int => array_search($term->id, $ids, true));
            $allowsMultiple = ($field['max_items'] ?? null) === null || $field['max_items'] > 1;

            $data[$handle] = $allowsMultiple
                ? $terms->map(fn (TaxonomyTerm $term): array => static::termAttributes($term))->values()->all()
                : (($term = $terms->first()) ? static::termAttributes($term) : null);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function termAttributes(TaxonomyTerm $term): array
    {
        return array_merge($term->data, ['id' => $term->id, 'slug' => $term->slug]);
    }

    protected static function wrapFluent(array $data): Fluent
    {
        return new Fluent(collect($data)->map(function ($value) {
            if (! is_array($value)) {
                return $value;
            }

            return array_is_list($value)
                ? collect($value)->map(fn ($item) => is_array($item) ? static::wrapFluent($item) : $item)->all()
                : static::wrapFluent($value);
        })->all());
    }
}
