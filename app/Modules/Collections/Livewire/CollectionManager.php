<?php

namespace App\Modules\Collections\Livewire;

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Collections\Models\Entry;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Collections')]
class CollectionManager extends Component
{
    public ?int $editingId = null;

    public string $handle = '';

    public string $title = '';

    public string $blueprint = '';

    public string $settings = '{}';

    #[Computed]
    public function collections()
    {
        return Collection::orderBy('title')->get();
    }

    #[Computed]
    public function availableBlueprints()
    {
        return Blueprint::orderBy('namespace')->orderBy('handle')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'handle', 'title', 'blueprint', 'settings']);
        $this->settings = '{}';
        Flux::modal('collection-form')->show();
    }

    public function edit(int $id): void
    {
        $collection = Collection::findOrFail($id);

        $this->editingId = $collection->id;
        $this->handle = $collection->handle;
        $this->title = $collection->title;
        $this->blueprint = (string) ($collection->settings['blueprint'] ?? '');

        $otherSettings = collect($collection->settings ?? [])->except('blueprint')->all();
        $this->settings = json_encode($otherSettings, JSON_PRETTY_PRINT);

        Flux::modal('collection-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'handle' => ['required', 'string', 'max:255', Rule::unique('collections', 'handle')->ignore($this->editingId)],
            'title' => ['required', 'string', 'max:255'],
            'blueprint' => ['nullable', 'string', Rule::exists('blueprints', 'handle')],
            'settings' => ['required', 'json'],
        ]);

        $settings = json_decode($validated['settings'], true);
        $settings['blueprint'] = $validated['blueprint'] ?: null;

        Collection::updateOrCreate(['id' => $this->editingId], [
            'handle' => $validated['handle'],
            'title' => $validated['title'],
            'settings' => $settings,
        ]);

        Flux::modal('collection-form')->close();
        Flux::toast(variant: 'success', text: __('Collection saved.'));

        unset($this->collections);
    }

    public function delete(int $id): void
    {
        $collection = Collection::findOrFail($id);

        $entryCount = Entry::where('collection', $collection->handle)->count();

        if ($entryCount > 0) {
            Flux::toast(
                variant: 'danger',
                text: __('Cannot delete ":title" — it still has :count entries. Delete those first.', [
                    'title' => $collection->title,
                    'count' => $entryCount,
                ]),
            );

            return;
        }

        $collection->delete();

        Flux::toast(variant: 'success', text: __('Collection deleted.'));

        unset($this->collections);
    }

    public function render(): View
    {
        return view('collections::livewire.collection-manager');
    }
}
