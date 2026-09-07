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
        $query = Entry::where('collection', $this->collection->handle)
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('data->title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->siteFilter !== '', fn ($query) => $query->where('site', $this->siteFilter));

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
     * @param  \Illuminate\Support\Collection<int, Entry>  $entries
     * @param  \Illuminate\Support\Collection<string, ?string>  $parentIds
     * @return \Illuminate\Support\Collection<int, Entry>
     */
    protected function flattenTree($entries, $parentIds, ?string $parentId, int $depth)
    {
        $siblings = $entries
            ->filter(fn (Entry $entry): bool => ($parentIds[$entry->id] ?? null) === $parentId)
            ->sort(function (Entry $a, Entry $b): int {
                $byOrder = ($a->order ?? PHP_INT_MAX) <=> ($b->order ?? PHP_INT_MAX);

                return $byOrder !== 0 ? $byOrder : $b->updated_at <=> $a->updated_at;
            })
            ->values();

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

    public function delete(string $id): void
    {
        $entry = Entry::where('collection', $this->collection->handle)->findOrFail($id);

        if ($this->collection->structured()) {
            app(CollectionTreeService::class)->removeEntry($this->collection->handle, $entry->site, $id);
        }

        $entry->delete();

        Flux::toast(variant: 'success', text: __('Entry deleted.'));

        unset($this->entries);
    }

    public function render(): View
    {
        return view('collections::livewire.entry-manager', [
            'defaultSite' => self::DEFAULT_SITE,
            'statuses' => EntryForm::STATUSES,
        ])->title(__(':collection entries', ['collection' => $this->collection->title]));
    }
}
