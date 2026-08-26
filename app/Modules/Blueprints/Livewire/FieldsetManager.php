<?php

namespace App\Modules\Blueprints\Livewire;

use App\Modules\Blueprints\Models\Fieldset;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fieldsets')]
class FieldsetManager extends Component
{
    public ?int $editingId = null;

    public string $handle = '';

    public string $data = '{}';

    #[Computed]
    public function fieldsets()
    {
        return Fieldset::orderBy('handle')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'handle', 'data']);
        $this->data = '{}';
        Flux::modal('fieldset-form')->show();
    }

    public function edit(int $id): void
    {
        $fieldset = Fieldset::findOrFail($id);

        $this->editingId = $fieldset->id;
        $this->handle = $fieldset->handle;
        $this->data = json_encode($fieldset->data, JSON_PRETTY_PRINT);

        Flux::modal('fieldset-form')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'handle' => ['required', 'string', 'max:255', Rule::unique('fieldsets', 'handle')->ignore($this->editingId)],
            'data' => ['required', 'json'],
        ]);

        $validated['data'] = json_decode($validated['data'], true);

        Fieldset::updateOrCreate(['id' => $this->editingId], $validated);

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
        return view('blueprints::livewire.fieldset-manager');
    }
}
