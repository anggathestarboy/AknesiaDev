<?php

namespace App\Modules\Navigation\Livewire;

use App\Modules\Collections\Models\Entry;
use App\Modules\Media\Models\AssetMeta;
use App\Modules\Navigation\Models\Navigation;
use App\Modules\Navigation\Services\NavigationTreeService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Edits a navigation's content. Two entirely different modes depending on
 * whether the navigation has a blueprint with fields:
 *
 * - Blueprint mode (the normal case): a single blueprint-driven form, just
 *   like GlobalSetForm/EntryForm/TermForm — no tree, no "Add item" button.
 *   Repeated menu items (e.g. links) are a "replicator" field the blueprint
 *   author defines themselves, with its own "Add row" button, mirroring
 *   Statamic's nav-via-fieldset concept.
 * - Legacy tree mode: the original free-form nested tree editor (add/move/
 *   duplicate/delete items, drag-and-drop). Only reachable for protected
 *   navigations with no blueprint, i.e. sidebar-admin — see
 *   NavigationManager, which disables "Edit tree" for anything else without
 *   blueprint fields.
 */
#[Title('Navigation')]
class NavigationTreeEditor extends Component
{
    public Navigation $navigation;

    /**
     * @var array<int, array>
     */
    public array $tree = [];

    /**
     * @var array<int, string>
     */
    public array $expanded = [];

    public ?string $deletingId = null;

    public ?string $movingId = null;

    public string $moveTargetParentId = '';

    public string $moveChildrenStrategy = '';

    public string $activeTab = 'main';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(Navigation $navigation): void
    {
        $this->navigation = $navigation;

        if ($this->usesBlueprint()) {
            $this->data = $this->fillFieldsData($this->blueprintFields(), $this->treeService()->load($navigation));

            return;
        }

        $this->tree = $this->treeService()->load($navigation);
    }

    public function usesBlueprint(): bool
    {
        return $this->blueprintFields() !== [];
    }

    #[Computed]
    public function blueprintFields(): array
    {
        return $this->navigation->effectiveBlueprint()?->data['fields'] ?? [];
    }

    #[Computed]
    public function blueprintTabs(): array
    {
        return $this->navigation->effectiveBlueprint()?->data['tabs'] ?? [['handle' => 'main', 'display' => 'Main']];
    }

    public function setActiveTab(string $handle): void
    {
        $this->activeTab = $handle;
    }

    /**
     * Recursively build a data array matching a field tree — see
     * GlobalSetForm::fillFieldsData(), which this mirrors.
     */
    protected function fillFieldsData(array $fields, ?array $existingData): array
    {
        $data = [];

        foreach ($fields as $field) {
            if ($field['type'] === 'replicator') {
                $existingRows = $existingData[$field['handle']] ?? [];
                $data[$field['handle']] = array_map(
                    fn (array $row): array => array_merge(
                        ['_uid' => $row['_uid'] ?? (string) Str::uuid()],
                        $this->fillFieldsData($field['replicator_fields'] ?? [], $row)
                    ),
                    $existingRows
                );

                continue;
            }

            if ($field['type'] === 'assets') {
                $data[$field['handle']] = $existingData[$field['handle']] ?? [];

                continue;
            }

            $data[$field['handle']] = $existingData[$field['handle']]
                ?? $field['default']
                ?? ($field['type'] === 'toggle' ? false : '');
        }

        return $data;
    }

    public function addReplicatorRow(array $path): void
    {
        $field = $this->replicatorFieldAt($path);

        if (! $field) {
            return;
        }

        $rows = &$this->dataAt($path);
        $rows[] = array_merge(['_uid' => (string) Str::uuid()], $this->fillFieldsData($field['replicator_fields'] ?? [], null));
    }

    public function removeReplicatorRow(array $path, int $rowIndex): void
    {
        $rows = &$this->dataAt($path);
        unset($rows[$rowIndex]);
        $rows = array_values($rows);
    }

    public function duplicateReplicatorRow(array $path, int $rowIndex): void
    {
        $rows = &$this->dataAt($path);

        if (! array_key_exists($rowIndex, $rows)) {
            return;
        }

        $duplicate = $rows[$rowIndex];
        $duplicate['_uid'] = (string) Str::uuid();
        array_splice($rows, $rowIndex + 1, 0, [$duplicate]);
    }

    /**
     * Drag-and-drop reorder handler for wire:sort (see partials/menu-list.blade.php).
     * $id is the dragged row's "_uid", $position its new zero-based index, and
     * $groupId is JSON `{"path": [...]}` identifying which rows array — each
     * nesting level gets its own wire:sort group, so a row can never be
     * dragged out of its own parent's list.
     */
    public function reorderReplicatorRow(string $id, int $position, string $groupId): void
    {
        $path = json_decode($groupId, true)['path'] ?? [];
        $rows = &$this->dataAt($path);
        $oldIndex = $this->findRowIndexByUid($rows, $id);

        if ($oldIndex === null) {
            return;
        }

        $values = array_values($rows);
        $moved = array_splice($values, $oldIndex, 1)[0];
        array_splice($values, $position, 0, [$moved]);
        $rows = $values;
    }

    protected function findRowIndexByUid(array $rows, string $uid): ?int
    {
        foreach ($rows as $index => $row) {
            if (($row['_uid'] ?? null) === $uid) {
                return $index;
            }
        }

        return null;
    }

    public function removeAsset(array $path, int $assetId): void
    {
        $ids = &$this->dataAt($path);
        $ids = collect($ids ?? [])->map(fn ($id): int => (int) $id)->reject(fn (int $id): bool => $id === $assetId)->values()->all();
    }

    /**
     * @param  array<int, string|int>  $path
     * @param  array<int, int>  $ids
     */
    #[On('assets-selected')]
    public function assetsSelected(array $path, array $ids): void
    {
        if (($path[0] ?? null) === '__bard__') {
            $this->dispatch('bard-image-selected', prefix: $path[1], mode: $path[2] ?? 'insert', urls: AssetMeta::whereIn('id', $ids)->get()->pluck('url')->all());

            return;
        }

        $ref = &$this->dataAt($path);
        $ref = array_values($ids);
    }

    /**
     * @return Collection<int, Entry>
     */
    public function linkableEntries(array $allowedCollectionHandles)
    {
        return Entry::when($allowedCollectionHandles !== [], fn ($query) => $query->whereIn('collection', $allowedCollectionHandles))
            ->orderBy('collection')
            ->orderBy('slug')
            ->get();
    }

    /**
     * @return Collection<int, AssetMeta>
     */
    public function selectedAssets(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }

        $assets = AssetMeta::whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $assets->get((int) $id))->filter()->values();
    }

    protected function replicatorFieldAt(array $path): ?array
    {
        $fields = $this->blueprintFields();
        $field = null;

        foreach ($path as $segment) {
            if (is_int($segment)) {
                continue;
            }

            $field = collect($fields)->firstWhere('handle', $segment);

            if (! $field) {
                return null;
            }

            $fields = $field['replicator_fields'] ?? [];
        }

        return $field;
    }

    protected function &dataAt(array $path)
    {
        $ref = &$this->data;

        foreach ($path as $segment) {
            $ref = &$ref[$segment];
        }

        return $ref;
    }

    protected function fieldRules(): array
    {
        return $this->fieldRulesFor($this->blueprintFields(), 'data');
    }

    protected function fieldRulesFor(array $fields, string $prefix): array
    {
        $rules = [];

        foreach ($fields as $field) {
            $key = "{$prefix}.{$field['handle']}";

            if ($field['type'] === 'replicator') {
                $rules[$key] = ['array'];
                $rules = array_merge($rules, $this->fieldRulesFor($field['replicator_fields'] ?? [], "{$key}.*"));

                continue;
            }

            if ($field['type'] === 'assets') {
                $arrayRules = [];

                if ($field['validate_sometimes'] ?? false) {
                    $arrayRules[] = 'sometimes';
                }

                $arrayRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';
                $arrayRules[] = 'array';

                if (($field['min_items'] ?? null) !== null) {
                    $arrayRules[] = "min:{$field['min_items']}";
                }

                if (($field['max_items'] ?? null) !== null) {
                    $arrayRules[] = "max:{$field['max_items']}";
                }

                $rules[$key] = $arrayRules;
                $rules["{$key}.*"] = ['integer', Rule::exists('assets_meta', 'id')->where('container', $field['options'] ?? null)];

                continue;
            }

            if ($field['type'] === 'checkbox') {
                $arrayRules = [];

                if ($field['validate_sometimes'] ?? false) {
                    $arrayRules[] = 'sometimes';
                }

                $arrayRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';
                $arrayRules[] = 'array';

                if (($field['min_items'] ?? null) !== null) {
                    $arrayRules[] = "min:{$field['min_items']}";
                }

                if (($field['max_items'] ?? null) !== null) {
                    $arrayRules[] = "max:{$field['max_items']}";
                }

                $rules[$key] = $arrayRules;
                $rules["{$key}.*"] = [Rule::in($field['options'] ?? [])];

                continue;
            }

            if ($field['type'] === 'link') {
                $linkRules = [];

                if ($field['validate_sometimes'] ?? false) {
                    $linkRules[] = 'sometimes';
                }

                $linkRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';
                $linkRules[] = 'string';
                $linkRules[] = 'max:2048';

                if (! empty($field['validation'])) {
                    $linkRules[] = $field['validation'];
                }

                $rules[$key] = $linkRules;

                continue;
            }

            $fieldRules = [];

            if ($field['validate_sometimes'] ?? false) {
                $fieldRules[] = 'sometimes';
            }

            $fieldRules[] = ($field['required'] ?? false) ? 'required' : 'nullable';

            $fieldRules[] = match ($field['type']) {
                'number' => 'numeric',
                'toggle' => 'boolean',
                'date' => 'date',
                'select', 'radio' => Rule::in($field['options'] ?? []),
                default => 'string',
            };

            if (! empty($field['validation'])) {
                $fieldRules[] = $field['validation'];
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    /* ---------- Legacy tree mode (sidebar-admin only) ---------- */

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

    /**
     * Reorders within the root list only. Cross-parent moves go through
     * askMove()/confirmMove() instead — dragging an item between separate
     * sortable lists (root <-> a parent's children) was unreliable and
     * could corrupt sibling data, so each list here only reorders itself.
     */
    public function sortRoot($id, $position): void
    {
        $this->tree = $this->treeService()->withMovedItem($this->tree, $id, null, (int) $position);
    }

    /**
     * Reorders within one parent's children list only. See sortRoot().
     */
    public function sortChildren($id, $position, $parentId): void
    {
        $this->tree = $this->treeService()->withMovedItem($this->tree, $id, $parentId, (int) $position);
    }

    public function askMove(string $id): void
    {
        $this->movingId = $id;
        $this->moveTargetParentId = '';
        $this->moveChildrenStrategy = '';
        Flux::modal('move-item')->show();
    }

    /**
     * Root items that $movingId can be moved under, i.e. every root item
     * except itself (only root items can be parents, since depth is capped
     * at 2 levels).
     *
     * @return array<int, array>
     */
    #[Computed]
    public function moveTargets(): array
    {
        return collect($this->tree)
            ->reject(fn (array $item): bool => $item['id'] === $this->movingId)
            ->values()
            ->all();
    }

    public function movingItemHasChildren(): bool
    {
        if ($this->movingId === null) {
            return false;
        }

        $item = $this->treeService()->findItem($this->tree, $this->movingId);

        return $item !== null && $item['children'] !== [];
    }

    public function confirmMove(): void
    {
        if ($this->movingId === null) {
            return;
        }

        $service = $this->treeService();
        $tree = $this->tree;
        $targetParentId = $this->moveTargetParentId !== '' ? $this->moveTargetParentId : null;

        $movingItem = $service->findItem($tree, $this->movingId);

        if ($movingItem === null) {
            return;
        }

        if ($movingItem['children'] !== [] && $targetParentId !== null) {
            if ($this->moveChildrenStrategy === '') {
                Flux::toast(variant: 'danger', text: __("Choose what happens to this item's children first."));

                return;
            }

            $tree = $this->moveChildrenStrategy === 'promote'
                ? $service->withPromotedChildren($tree, $this->movingId)
                : $service->withClearedChildren($tree, $this->movingId);
        }

        $siblings = $targetParentId !== null
            ? $service->findItem($tree, $targetParentId)['children']
            : $tree;

        try {
            $this->tree = $service->withMovedItem($tree, $this->movingId, $targetParentId, count($siblings));
        } catch (InvalidArgumentException $e) {
            Flux::toast(variant: 'danger', text: $e->getMessage());

            return;
        }

        $this->movingId = null;
        Flux::modal('move-item')->close();
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

    /* ---------- Shared ---------- */

    public function save(): void
    {
        if ($this->usesBlueprint()) {
            $validated = $this->validate($this->fieldRules());
            $this->treeService()->save($this->navigation, $this->stripRowUids($validated['data'] ?? []));

            Flux::toast(variant: 'success', text: __('Navigation saved.'));

            return;
        }

        $this->treeService()->save($this->navigation, $this->tree);

        Flux::toast(variant: 'success', text: __('Navigation saved.'));
    }

    protected function stripRowUids(array $data): array
    {
        unset($data['_uid']);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->stripRowUids($value);
            }
        }

        return $data;
    }

    protected function treeService(): NavigationTreeService
    {
        return app(NavigationTreeService::class);
    }

    public function render(): View
    {
        return view($this->usesBlueprint()
            ? 'navigation::livewire.navigation-content-editor'
            : 'navigation::livewire.navigation-tree-editor');
    }
}
