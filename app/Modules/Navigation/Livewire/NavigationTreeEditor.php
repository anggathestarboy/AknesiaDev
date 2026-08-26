<?php

namespace App\Modules\Navigation\Livewire;

use App\Modules\Collections\Models\Entry;
use App\Modules\Navigation\Models\Navigation;
use App\Modules\Navigation\Services\NavigationTreeService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Navigation tree')]
class NavigationTreeEditor extends Component
{
    public Navigation $navigation;

    /**
     * @var array<int, array>
     */
    public array $tree = [];

    /**
     * Item ids currently expanded in the accordion. UI-only state, never
     * persisted alongside the tree in `settings->tree`.
     *
     * @var array<int, string>
     */
    public array $expanded = [];

    /**
     * Id of the item pending confirmation in the delete modal.
     */
    public ?string $deletingId = null;

    public function mount(Navigation $navigation): void
    {
        $this->navigation = $navigation;
        $this->tree = $this->treeService()->load($navigation);
    }

    #[Computed]
    public function pages()
    {
        return Entry::orderBy('slug')->get(['id', 'slug', 'data']);
    }

    public function toggle(string $id): void
    {
        if (in_array($id, $this->expanded, true)) {
            $this->expanded = array_values(array_diff($this->expanded, [$id]));

            return;
        }

        $this->expanded[] = $id;
    }

    public function addRoot(): void
    {
        $this->tree = $this->treeService()->withAddedItem($this->tree, null, $this->blankAttributes());
        $this->expanded[] = $this->tree[array_key_last($this->tree)]['id'];
    }

    public function addChild(string $parentId): void
    {
        $this->tree = $this->treeService()->withAddedItem($this->tree, $parentId, $this->blankAttributes());

        $children = $this->treeService()->findItem($this->tree, $parentId)['children'];

        if (! in_array($parentId, $this->expanded, true)) {
            $this->expanded[] = $parentId;
        }

        $this->expanded[] = $children[array_key_last($children)]['id'];
    }

    public function duplicateItem(string $id): void
    {
        $this->tree = $this->treeService()->withDuplicatedItem($this->tree, $id);
    }

    public function askDelete(string $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete-item')->show();
    }

    public function confirmDelete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $this->tree = $this->treeService()->withRemovedItem($this->tree, $this->deletingId);
        $this->deletingId = null;

        Flux::modal('confirm-delete-item')->close();
    }

    public function sortItem($id, $position, $groupId): void
    {
        try {
            $this->tree = $this->treeService()->withMovedItem(
                $this->tree,
                $id,
                $groupId === 'root' ? null : $groupId,
                (int) $position
            );
        } catch (InvalidArgumentException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());
        }
    }

    /**
     * Fields are bound directly to `$tree` (e.g. wire:model.blur="tree.0.title"),
     * so a type change lands here to normalize the type-dependent url/page_id
     * exclusivity in memory. Nothing here touches the database — only the
     * explicit save() action (or Ctrl+S) persists the tree.
     */
    public function updated(string $name): void
    {
        if (! str_starts_with($name, 'tree.') || ! str_ends_with($name, '.type')) {
            return;
        }

        $itemPath = Str::before(Str::after($name, 'tree.'), '.type');
        $type = data_get($this->tree, "{$itemPath}.type");

        data_set($this->tree, "{$itemPath}.page_id", $type === 'page' ? data_get($this->tree, "{$itemPath}.page_id") : null);
        data_set($this->tree, "{$itemPath}.url", $type === 'page' ? null : data_get($this->tree, "{$itemPath}.url"));
    }

    public function save(): void
    {
        $this->treeService()->save($this->navigation, $this->tree);

        Flux::toast(variant: 'success', text: __('Navigation saved.'));
    }

    public function resolveUrl(array $item): ?string
    {
        return $this->treeService()->resolveUrl($item);
    }

    /**
     * @return array<string, mixed>
     */
    protected function blankAttributes(): array
    {
        return ['type' => 'internal', 'title' => '', 'url' => null, 'target' => '_self'];
    }

    protected function treeService(): NavigationTreeService
    {
        return app(NavigationTreeService::class);
    }

    public function render(): View
    {
        return view('navigation::livewire.navigation-tree-editor');
    }
}
