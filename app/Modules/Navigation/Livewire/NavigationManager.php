<?php

namespace App\Modules\Navigation\Livewire;

use App\Modules\Blueprints\Models\Blueprint;
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

    public string $blueprint = '';

    /**
     * Whether saving this (new) navigation should also create a blueprint
     * for it, namespaced "navigations.{handle}", instead of requiring one to
     * be picked from $blueprint. Only relevant on create. Mirrors
     * TaxonomyManager::$createBlueprint.
     */
    public bool $createBlueprint = true;

    #[Computed]
    public function navigations()
    {
        return Navigation::orderBy('title')->get();
    }

    #[Computed]
    public function availableBlueprints()
    {
        return Blueprint::orderBy('namespace')->orderBy('handle')->get();
    }

    public function create(): void
    {
        auth()->user()->authorizePermission('configure navigation');

        $this->reset(['editingId', 'handle', 'title', 'blueprint']);
        $this->createBlueprint = true;
        Flux::modal('navigation-form')->show();
    }

    public function edit(int $id): void
    {
        auth()->user()->authorizePermission('configure navigation');

        $navigation = Navigation::findOrFail($id);

        if ($this->isProtected($navigation)) {
            Flux::toast(variant: 'danger', text: __('This navigation is protected and cannot be edited.'));

            return;
        }

        $this->editingId = $navigation->id;
        $this->handle = $navigation->handle;
        $this->title = $navigation->title;
        $this->blueprint = (string) ($navigation->effectiveBlueprint()?->handle ?? '');
        $this->createBlueprint = false;

        Flux::modal('navigation-form')->show();
    }

    public function regenerateHandle(): void
    {
        $this->handle = Str::slug($this->title);
    }

    public function save(): void
    {
        auth()->user()->authorizePermission('configure navigation');

        $validated = $this->validate([
            'handle' => ['required', 'string', 'max:255', Rule::unique('navigations', 'handle')->ignore($this->editingId)],
            'title' => ['required', 'string', 'max:255'],
            'blueprint' => [$this->createBlueprint ? 'exclude' : 'nullable', 'string', Rule::exists('blueprints', 'handle')],
        ]);

        $navigation = Navigation::updateOrCreate(['id' => $this->editingId], [
            'handle' => $validated['handle'],
            'title' => $validated['title'],
            'settings' => ['blueprint' => $validated['blueprint'] ?? null],
        ]);

        $blueprintCreated = false;

        if ($this->createBlueprint) {
            $blueprint = Blueprint::firstOrCreate(
                ['namespace' => "navigations.{$navigation->handle}", 'handle' => $navigation->handle],
                ['data' => ['tabs' => [['handle' => 'main', 'display' => 'Main', 'sections' => []]], 'fields' => []]]
            );
            $blueprintCreated = $blueprint->wasRecentlyCreated;
        }

        Flux::modal('navigation-form')->close();
        unset($this->navigations);

        if ($blueprintCreated) {
            Flux::toast(variant: 'success', text: __('Navigation saved — add fields to its blueprint so they show up on Edit tree.'));
            $this->redirectRoute('blueprints.index', navigate: true);

            return;
        }

        Flux::toast(variant: 'success', text: __('Navigation saved.'));
    }

    public function delete(int $id): void
    {
        auth()->user()->authorizePermission('configure navigation');

        $navigation = Navigation::findOrFail($id);

        if ($this->isProtected($navigation)) {
            Flux::toast(variant: 'danger', text: __('This navigation is protected and cannot be deleted.'));

            return;
        }

        $navigation->delete();

        Flux::toast(variant: 'success', text: __('Navigation deleted.'));

        unset($this->navigations);
    }

    public function isProtected(Navigation $navigation): bool
    {
        return in_array($navigation->handle, config('cms.protected_navigations', []), true);
    }

    public function render(): View
    {
        return view('navigation::livewire.navigation-manager');
    }
}
