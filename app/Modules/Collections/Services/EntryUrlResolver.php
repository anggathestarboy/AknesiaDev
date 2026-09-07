<?php

namespace App\Modules\Collections\Services;

use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Sites\Models\Site;
use Illuminate\Support\Carbon;

/**
 * Resolves an entry's public path/URL from its collection's route template
 * (see Collection::routeForSite()) — shared by the entry form's "Visit"
 * button and the public-facing PageController, so both agree on the same
 * URL for the same entry.
 */
class EntryUrlResolver
{
    public function __construct(protected CollectionTreeService $trees) {}

    /**
     * The entry's path (no domain), or null if its collection has no route
     * configured for its site. An entry with order = 1 is its site's
     * homepage — its path is always empty (the site's own root), ignoring
     * the route template's {slug} entirely.
     */
    public function path(Collection $collection, Entry $entry): ?string
    {
        $template = $collection->routeForSite($entry->site);

        if (! $template) {
            return null;
        }

        if ($entry->order === 1) {
            return '';
        }

        return $this->resolvePath($collection, $template, $entry);
    }

    /**
     * The entry's full public URL (site URL + path), or null if its
     * collection has no route configured for its site, or the site itself
     * doesn't exist.
     */
    public function url(Collection $collection, Entry $entry): ?string
    {
        $path = $this->path($collection, $entry);

        if ($path === null) {
            return null;
        }

        $site = Site::where('handle', $entry->site)->first();

        if (! $site) {
            return null;
        }

        $base = rtrim($site->url, '/');

        return $path === '' ? ($base ?: '/') : "{$base}/{$path}";
    }

    /**
     * Fills a route template's {slug}/{year}/{month}/{day}/{parent_uri}
     * placeholders. {parent_uri} recurses into the parent entry's own
     * resolved path (which may itself have a parent), so a uniform
     * "{parent_uri}/{slug}" template naturally nests the full ancestor
     * chain.
     */
    protected function resolvePath(Collection $collection, string $template, Entry $entry): string
    {
        $date = $entry->date ? Carbon::parse($entry->date) : null;
        $parentId = $this->trees->parentIdOf($collection->handle, $entry->site, $entry->id);
        $parent = $parentId ? Entry::find($parentId) : null;

        $path = strtr($template, [
            '{slug}' => $entry->slug ?: '',
            '{year}' => $date?->format('Y') ?? '',
            '{month}' => $date?->format('m') ?? '',
            '{day}' => $date?->format('d') ?? '',
            '{parent_uri}' => $parent ? ($this->path($collection, $parent) ?? '') : '',
        ]);

        return trim($path, '/');
    }
}
