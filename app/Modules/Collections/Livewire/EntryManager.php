<?php

namespace App\Modules\Collections\Livewire;

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
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

    #[Computed]
    public function entries()
    {
        return Entry::where('collection', $this->collection->handle)
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('slug', 'like', "%{$this->search}%")
                        ->orWhere('data->title', 'like', "%{$this->search}%");
                });
            })
            ->when($this->siteFilter !== '', fn ($query) => $query->where('site', $this->siteFilter))
            ->orderByDesc('updated_at')
            ->get();
    }

    #[Computed]
    public function availableSites()
    {
        return Site::orderBy('order')->get();
    }

    /**
     * All blueprints an entry in this collection is allowed to use: the
     * collection's explicitly picked blueprint (if any) plus every
     * blueprint namespaced to this collection (e.g. "collections.pages").
     */
    #[Computed]
    public function availableBlueprints()
    {
        $explicitHandle = $this->collection->settings['blueprint'] ?? null;

        return Blueprint::where('namespace', "collections.{$this->collection->handle}")
            ->when($explicitHandle, fn ($query) => $query->orWhere('handle', $explicitHandle))
            ->orderBy('handle')
            ->get()
            ->unique('id')
            ->values();
    }

    public function delete(string $id): void
    {
        Entry::where('collection', $this->collection->handle)->findOrFail($id)->delete();

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
