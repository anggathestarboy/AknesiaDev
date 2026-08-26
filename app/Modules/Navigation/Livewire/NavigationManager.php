<?php

namespace App\Modules\Navigation\Livewire;

use App\Modules\Navigation\Models\Navigation;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Navigation')]
class NavigationManager extends Component
{
    public ?int $editingId = null;

    public string $handle = '';

    public string $title = '';

    /**
     * Once the user types into the handle field directly, it stops
     * auto-following the title (matches Statamic's slug-follows-title
     * behavior).
     */
    public bool $handleManuallyEdited = false;

    #[Computed]
    public function navigations()
    {
        return Navigation::orderBy('title')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'handle', 'title', 'handleManuallyEdited']);
        Flux::modal('navigation-form')->show();
    }

    public function edit(int $id): void
    {
        $navigation = Navigation::findOrFail($id);

        $this->editingId = $navigation->id;
        $this->handle = $navigation->handle;
        $this->title = $navigation->title;
        $this->handleManuallyEdited = true;

        Flux::modal('navigation-form')->show();
    }

    public function updated(string $name): void
    {
        if ($name === 'title' && ! $this->handleManuallyEdited) {
            $this->handle = Str::slug($this->title);
        }

        if ($name === 'handle') {
            $this->handleManuallyEdited = true;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'handle' => ['required', 'string', 'max:255', Rule::unique('navigations', 'handle')->ignore($this->editingId)],
            'title' => ['required', 'string', 'max:255'],
        ]);

        Navigation::updateOrCreate(['id' => $this->editingId], $validated);

        Flux::modal('navigation-form')->close();
        Flux::toast(variant: 'success', text: __('Navigation saved.'));

        unset($this->navigations);
    }

    public function delete(int $id): void
    {
        Navigation::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Navigation deleted.'));

        unset($this->navigations);
    }

    public function render(): View
    {
        return view('navigation::livewire.navigation-manager');
    }
}
