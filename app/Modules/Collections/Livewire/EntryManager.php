<?php

namespace App\Modules\Collections\Livewire;

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use App\Modules\Collections\Services\CollectionTreeService;
use App\Modules\Sites\Models\Site;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class EntryManager extends Component
{
    const DEFAULT_SITE = 'indonesia';

    public Collection $collection;

    public string $search = '';

    public string $siteFilter = '';

    public function mount(Collection $collection): void
    {
        $this->collection = $collection;
        $this->siteFilter = Site::orderBy('order')->value('handle') ?? '';
    }

    /**
     * The entry with order = 1 is treated as this collection's homepage —
     * pinned first in the listing, regardless of the sort below. For a
     * structured collection, entries are instead listed as a flattened
     * tree — each entry immediately followed by its children — with
     * siblings at every level ordered by their own "order" (entries
     * without one last), so it stays consistent with the homepage rule.
     * Each entry gets transient `depth` and `parentTitle` attributes for
     * the view to indent and label it with.
     */
    #[Computed]
    public function entries()
    {
        $query = $this->baseQuery();

        if (! $this->collection->structured()) {
            return $query->orderByRaw('`order` != 1')->orderByDesc('updated_at')->get();
        }

        $entries = $query->get();
        $trees = app(CollectionTreeService::class);
        $parentIds = $entries->mapWithKeys(fn (Entry $entry): array => [
            $entry->id => $trees->parentIdOf($this->collection->handle, $entry->site, $entry->id),
        ]);

        return $this->flattenTree($entries, $parentIds, null, 0);
    }

    /**
     * A nested tree of entries for a "Structured" collection — each entry
     * carries a "children" attribute holding its own child entries,
     * recursively, sorted the same way as entries()'s flattened list. Used
     * by the drag-and-drop tree view, which needs real nesting (rather than
     * entries()'s flat, depth-annotated list) so each parent's children can
     * be their own independent drag-and-drop group.
     */
    #[Computed]
    public function entryTree()
    {
        $entries = $this->baseQuery()->get();
        $trees = app(CollectionTreeService::class);
        $parentIds = $entries->mapWithKeys(fn (Entry $entry): array => [
            $entry->id => $trees->parentIdOf($this->collection->handle, $entry->site, $entry->id),
        ]);

        return $this->buildTree($entries, $parentIds, null);
    }

    protected function baseQuery()
    {
        return Entry::where('collection', $this->collection->handle)
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('data->title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->siteFilter !== '', fn ($query) => $query->where('site', $this->siteFilter));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Entry>  $entries
     * @param  \Illuminate\Support\Collection<string, ?string>  $parentIds
     * @return \Illuminate\Support\Collection<int, Entry>
     */
    protected function flattenTree($entries, $parentIds, ?string $parentId, int $depth)
    {
        $siblings = $this->sortedSiblings($entries, $parentIds, $parentId);

        $parentTitle = $parentId ? $entries->firstWhere('id', $parentId)?->data['title'] ?? null : null;

        $result = collect();

        foreach ($siblings as $entry) {
            $entry->setAttribute('depth', $depth);
            $entry->setAttribute('parentTitle', $parentTitle);
            $result->push($entry);
            $result = $result->merge($this->flattenTree($entries, $parentIds, $entry->id, $depth + 1));
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Entry>  $entries
     * @param  \Illuminate\Support\Collection<string, ?string>  $parentIds
     * @return \Illuminate\Support\Collection<int, Entry>
     */
    protected function buildTree($entries, $parentIds, ?string $parentId)
    {
        return $this->sortedSiblings($entries, $parentIds, $parentId)
            ->map(function (Entry $entry) use ($entries, $parentIds) {
                $entry->setAttribute('children', $this->buildTree($entries, $parentIds, $entry->id));

                return $entry;
            });
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Entry>  $entries
     * @param  \Illuminate\Support\Collection<string, ?string>  $parentIds
     * @return \Illuminate\Support\Collection<int, Entry>
     */
    protected function sortedSiblings($entries, $parentIds, ?string $parentId)
    {
        return $entries
            ->filter(fn (Entry $entry): bool => ($parentIds[$entry->id] ?? null) === $parentId)
            ->sort(function (Entry $a, Entry $b): int {
                $byOrder = ($a->order ?? PHP_INT_MAX) <=> ($b->order ?? PHP_INT_MAX);

                return $byOrder !== 0 ? $byOrder : $b->updated_at <=> $a->updated_at;
            })
            ->values();
    }

    public function reorderRoot($id, $position): void
    {
        $this->reorder((string) $id, (int) $position, null);
    }

    public function reorderChildren($id, $position, $parentId): void
    {
        $this->reorder((string) $id, (int) $position, (string) $parentId);
    }

    /**
     * Renumbers the dragged entry's sibling group (root entries, or one
     * parent's children) so their `order` matches the new drag position.
     * Reordering is confined to a single site and to the dragged entry's
     * existing parent — each parent's children render as their own
     * independent drag-and-drop group (see entry-tree-row.blade.php), so an
     * entry can never be dragged out from under its parent. The root
     * group's first position becomes order 1, which EntryUrlResolver and
     * this listing both treat as the site's homepage; child groups start
     * numbering at 2 so a child can never accidentally become the homepage.
     */
    protected function reorder(string $id, int $position, ?string $parentId): void
    {
        if (! $this->collection->orderable() || $this->siteFilter === '') {
            return;
        }

        $all = Entry::where('collection', $this->collection->handle)->where('site', $this->siteFilter)->get();
        $moved = $all->firstWhere('id', $id);

        if ($moved === null) {
            return;
        }

        if ($this->collection->structured()) {
            $trees = app(CollectionTreeService::class);
            $siblings = $all->filter(fn (Entry $entry): bool => $trees->parentIdOf($this->collection->handle, $this->siteFilter, $entry->id) === $parentId);
        } else {
            $siblings = $all;
        }

        $siblings = $siblings->sortBy(fn (Entry $entry) => $entry->order ?? PHP_INT_MAX)
            ->reject(fn (Entry $entry): bool => $entry->id === $id)
            ->values();
        $siblings->splice(min($position, $siblings->count()), 0, [$moved]);

        $base = $parentId === null ? 1 : 2;

        foreach ($siblings as $index => $entry) {
            $entry->update(['order' => $index + $base]);
        }

        unset($this->entries, $this->entryTree);
    }

    #[Computed]
    public function availableSites()
    {
        return Site::orderBy('order')->get();
    }

    /**
     * All blueprints an entry in this collection is allowed to use: every
     * blueprint namespaced to this collection (e.g. "collections.pages").
     */
    #[Computed]
    public function availableBlueprints()
    {
        return Blueprint::where('namespace', "collections.{$this->collection->handle}")
            ->orderBy('handle')
            ->get();
    }

    /**
     * This entry's other-language versions — every entry in the same
     * "origin family" (its origin, and every entry translated from it),
     * excluding itself. Mirrors TermForm::siblingTermForSite()'s
     * origin/origin_id relationship.
     *
     * @return \Illuminate\Support\Collection<int, Entry>
     */
    protected function localeSiblings(Entry $entry)
    {
        $originId = $entry->origin_id ?? $entry->id;

        return Entry::where('collection', $this->collection->handle)
            ->where('id', '!=', $entry->id)
            ->where(fn ($query) => $query->where('id', $originId)->orWhere('origin_id', $originId))
            ->get();
    }

    /**
     * Number of other-language versions this entry has — shown in the
     * delete-confirmation modal so the "also delete" checkbox isn't a
     * mystery when there's nothing else to delete.
     */
    public function localeSiblingCount(Entry $entry): int
    {
        return $this->localeSiblings($entry)->count();
    }

    public function delete(string $id, bool $deleteLocaleSiblings = true): void
    {
        $entry = Entry::where('collection', $this->collection->handle)->findOrFail($id);

        if ($deleteLocaleSiblings) {
            foreach ($this->localeSiblings($entry) as $sibling) {
                if ($this->collection->structured()) {
                    app(CollectionTreeService::class)->removeEntry($this->collection->handle, $sibling->site, $sibling->id);
                }

                $sibling->delete();
            }
        }

        if ($this->collection->structured()) {
            app(CollectionTreeService::class)->removeEntry($this->collection->handle, $entry->site, $id);
        }

        $entry->delete();

        Flux::toast(variant: 'success', text: __('Entry deleted.'));

        unset($this->entries, $this->entryTree);
    }

    public function render(): View
    {
        return view('collections::livewire.entry-manager', [
            'defaultSite' => self::DEFAULT_SITE,
            'statuses' => EntryForm::STATUSES,
            'orderable' => $this->collection->orderable() && $this->search === '' && $this->siteFilter !== '',
        ])->title(__(':collection entries', ['collection' => $this->collection->title]));
    }
}
