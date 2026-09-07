<?php

namespace App\Modules\Collections\Http\Controllers;

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Collections\Services\EntryUrlResolver;
use App\Modules\Sites\Models\Site;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Public-facing catch-all: renders a published entry at the URL resolved
 * from its collection's route template (see Collection::routeForSite() and
 * EntryUrlResolver), using the Blade template picked in its blueprint
 * (Blueprint::data.template — see BlueprintManager).
 */
class PageController
{
    public function show(Request $request, string $uri = ''): View
    {
        $site = $this->resolveSite($request);

        if (! $site) {
            throw new NotFoundHttpException;
        }

        $entry = $this->findEntry($site->handle, trim($uri, '/'));

        if (! $entry) {
            throw new NotFoundHttpException;
        }

        $template = Blueprint::where('handle', $entry->blueprint)->value('data');
        $template = $template['template'] ?? null;

        if (! $template || ! view()->exists($template)) {
            throw new NotFoundHttpException;
        }

        return view($template, [
            'entry' => $entry,
            'data' => $entry->data,
            'site' => $site,
        ]);
    }

    /**
     * Looks up a published entry by its cached uri first (fast, indexed).
     * If nothing matches — e.g. the entry predates uri being stored, or an
     * ancestor's slug changed since — falls back to live-resolving every
     * routable entry on this site and backfilling the match's uri for next
     * time.
     *
     * ponytail: O(n) routable entries on a cache miss; fine at this scale,
     * revisit (e.g. resolve+store on every save cascading to descendants)
     * if this becomes a hot path with many entries.
     */
    protected function findEntry(string $site, string $uri): ?Entry
    {
        $entry = Entry::where('site', $site)->where('status', 'published')->where('uri', $uri)->first();

        if ($entry) {
            return $entry;
        }

        $resolver = app(EntryUrlResolver::class);
        $routableCollections = Collection::all()->filter(fn (Collection $collection): bool => $collection->routeForSite($site) !== null);

        foreach ($routableCollections as $collection) {
            $candidates = Entry::where('collection', $collection->handle)
                ->where('site', $site)
                ->where('status', 'published')
                ->get();

            foreach ($candidates as $candidate) {
                if ($resolver->path($collection, $candidate) === $uri) {
                    $candidate->update(['uri' => $uri]);

                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * ponytail: substring match against each site's configured URL, falling
     * back to the first site — good enough for one domain per site or a
     * single-site setup; a dedicated exact-host lookup would be needed for
     * true multi-domain routing.
     */
    protected function resolveSite(Request $request): ?Site
    {
        $host = $request->getHost();

        return Site::query()->where('url', 'like', "%{$host}%")->first()
            ?? Site::orderBy('order')->first();
    }
}
