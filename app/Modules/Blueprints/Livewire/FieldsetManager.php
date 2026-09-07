<?php

namespace App\Modules\Blueprints\Livewire;

use App\Models\Taxonomy;
use App\Modules\Blueprints\Models\Fieldset;
use App\Modules\Blueprints\Support\HasFieldBuilder;
use App\Modules\Collections\Models\Collection;
use App\Modules\Media\Models\AssetContainer;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * A reusable group of fields (Statamic's "fieldset" concept) — unlike a
 * blueprint, a fieldset has no namespace and no tabs, just a flat list of
 * fields, meant to be imported into one or more blueprints later.
 */
#[Title('Fieldsets')]
class FieldsetManager extends Component
{
    use HasFieldBuilder;

    /**
     * The single implicit tab every fieldset's fields belong to — fieldsets
     * have no tabs/sections of their own, but the shared field-editor
     * partial expects a $tabs list to check for a tab/section picker.
     */
    const TABS = [['handle' => 'main', 'display' => 'Main', 'sections' => []]];

    public ?int $editingId = null;

    public string $handle = '';

    #[Computed]
    public function fieldsets()
    {
        return Fieldset::orderBy('handle')->get();
    }

    #[Computed]
    public function availableAssetContainers()
    {
        return AssetContainer::orderBy('title')->get();
    }

    #[Computed]
    public function availableCollections()
    {
        return Collection::orderBy('title')->get();
    }

    #[Computed]
    public function availableFieldsets()
    {
        return Fieldset::orderBy('handle')->get();
    }

    #[Computed]
    public function availableTaxonomies()
    {
        return Taxonomy::orderBy('title')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'handle', 'fields']);
        $this->addField();
        Flux::modal('fieldset-form')->show();
    }

    public function edit(int $id): void
    {
        $fieldset = Fieldset::findOrFail($id);

        $this->editingId = $fieldset->id;
        $this->handle = $fieldset->handle;
        $this->fields = collect($fieldset->data['fields'] ?? [])
            ->map(fn (array $field): array => $this->mapFieldFromStorage($field))
            ->all();

        if ($this->fields === []) {
            $this->addField();
        }

        Flux::modal('fieldset-form')->show();
    }

    public function save(): void
    {
        $this->fields = $this->normalizeFieldsTree($this->fields);

        $validated = $this->validate([
            'handle' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('fieldsets', 'handle')->ignore($this->editingId)],
        ]);

        $fieldErrors = $this->fields === [] ? ['fields' => __('Add at least one field.')] : [];
        $fieldErrors = array_merge($fieldErrors, $this->validateFieldsTree($this->fields, 'fields'));

        if ($fieldErrors !== []) {
            throw ValidationException::withMessages($fieldErrors);
        }

        Fieldset::updateOrCreate(['id' => $this->editingId], [
            'handle' => $validated['handle'],
            'data' => [
                'fields' => array_map(fn (array $field): array => $this->normalizeFieldForStorage($field, topLevel: false), $this->fields),
            ],
        ]);

        Flux::modal('fieldset-form')->close();
        Flux::toast(variant: 'success', text: __('Fieldset saved.'));

        unset($this->fieldsets);
    }

    public function delete(int $id): void
    {
        Fieldset::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Fieldset deleted.'));

        unset($this->fieldsets);
    }

    public function render(): View
    {
        return view('blueprints::livewire.fieldset-manager', [
            'fieldtypes' => static::$fieldtypes,
            'conditionOperators' => static::$conditionOperators,
            'assetContainers' => $this->availableAssetContainers,
            'linkableCollections' => $this->availableCollections,
            'fieldsets' => $this->availableFieldsets,
            'taxonomies' => $this->availableTaxonomies,
            'tabs' => self::TABS,
        ]);
    }
}
