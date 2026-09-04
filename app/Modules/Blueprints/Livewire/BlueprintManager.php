<?php

namespace App\Modules\Blueprints\Livewire;

use App\Models\Taxonomy;
use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
use App\Modules\Globals\Models\GlobalSet;
use App\Modules\Media\Models\AssetContainer;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Title('Blueprints')]
class BlueprintManager extends Component
{
    /**
     * Fieldtypes available in the field builder UI, mirroring Statamic's
     * fieldtype concept (a curated subset, not the full Statamic catalog).
     * "replicator" fields may themselves contain fields of any of these
     * types — including another "replicator" — so nesting is unlimited.
     *
     * @var array<string, string>
     */
    const FIELDTYPES = [
        'text' => 'Text',
        'textarea' => 'Textarea',
        'markdown' => 'Markdown',
        'number' => 'Number',
        'toggle' => 'Toggle',
        'date' => 'Date',
        'select' => 'Select',
        'radio' => 'Radio',
        'checkbox' => 'Checkbox',
        'replicator' => 'Replicator',
        'assets' => 'Assets',
        'link' => 'Link',
    ];

    /**
     * Fieldtypes whose value is picked from a comma-separated option list
     * (single value for select/radio, multiple for checkbox).
     *
     * @var array<int, string>
     */
    const OPTION_BASED_TYPES = ['select', 'radio', 'checkbox'];

    /**
     * Operators available for the "show when / hide when" field condition,
     * mirroring Statamic's conditional fields feature.
     *
     * @var array<string, string>
     */
    const CONDITION_OPERATORS = [
        'equals' => 'equals',
        'not' => 'not',
        'contains' => 'contains',
        'contains_any' => 'contains any',
        'strict' => '===',
    ];

    const CONDITION_MODES = ['always', 'show', 'hide'];

    public ?int $editingId = null;

    public string $namespace = '';

    public string $handle = '';

    /**
     * Groups top-level fields for display, mirroring Statamic's blueprint
     * tabs. The first tab is always the reserved "main" tab (handle fixed,
     * not user-editable, cannot be removed) — every field defaults there.
     * Each tab may further group its own fields into "sections" — a
     * sub-heading within the tab (a field's "section" is only meaningful
     * within its own tab; an empty section means "ungrouped").
     *
     * @var array<int, array{handle: string, display: string, sections: array<int, array{handle: string, display: string}>}>
     */
    public array $tabs = [
        ['handle' => 'main', 'display' => 'Main', 'sections' => [
            ['handle' => 'main', 'display' => 'Main'],
        ]],
    ];

    /**
     * Index into $tabs currently shown in edit mode (handle/display inputs)
     * instead of as a plain button — null when no tab is being edited.
     */
    public ?int $editingTabIndex = null;

    /**
     * [tabIndex, sectionIndex] of the section currently shown in edit mode
     * — null when no section is being edited.
     *
     * @var array{0: int, 1: int}|null
     */
    public ?array $editingSection = null;

    /**
     * A field tree: each field may itself contain a "replicator_fields"
     * array of the same shape, recursively, to any depth. "tab" is only
     * meaningful on top-level fields (which tab they display under) —
     * nested replicator sub-fields carry it too for shape consistency but
     * it's ignored there.
     *
     * @var array<int, array{
     *     handle: string, display: string, type: string, required: bool, options: string,
     *     placeholder: string, default: string, validation: string, instructions: string, sometimes: bool,
     *     condition_mode: string, condition_field: string, condition_operator: string, condition_value: string,
     *     min_items: string, max_items: string, tab: string,
     *     replicator_fields: array<int, mixed>,
     * }>
     */
    public array $fields = [];

    #[Computed]
    public function blueprints()
    {
        return Blueprint::orderBy('namespace')->orderBy('handle')->get();
    }

    /**
     * Blueprint namespaces are derived from existing collections and global
     * sets (e.g. a "blog" collection gives the namespace "collections.blog",
     * a "site-settings" global set gives "globals.site-settings"), mirroring
     * how Statamic scopes blueprints to the thing they belong to.
     */
    #[Computed]
    public function availableNamespaces()
    {
        $collectionNamespaces = Collection::orderBy('title')->get()
            ->map(fn (Collection $collection): array => [
                'value' => "collections.{$collection->handle}",
                'label' => "{$collection->title} (collections.{$collection->handle})",
            ]);

        $globalSetNamespaces = GlobalSet::orderBy('title')->get()
            ->map(fn (GlobalSet $globalSet): array => [
                'value' => "globals.{$globalSet->handle}",
                'label' => "{$globalSet->title} (globals.{$globalSet->handle})",
            ]);

        $taxonomyNamespaces = Taxonomy::orderBy('title')->get()
            ->map(fn (Taxonomy $taxonomy): array => [
                'value' => "taxonomies.{$taxonomy->handle}",
                'label' => "{$taxonomy->title} (taxonomies.{$taxonomy->handle})",
            ]);

        return $collectionNamespaces->concat($globalSetNamespaces)->concat($taxonomyNamespaces)->values();
    }

    /**
     * Asset containers an "assets" field can be scoped to (see the Media
     * module's asset_containers table).
     */
    #[Computed]
    public function availableAssetContainers()
    {
        return AssetContainer::orderBy('title')->get();
    }

    /**
     * Collections a "link" field's allow-list can be scoped to.
     */
    #[Computed]
    public function availableCollections()
    {
        return Collection::orderBy('title')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'namespace', 'handle', 'fields', 'tabs', 'editingTabIndex', 'editingSection']);
        $this->addField('main', 'main');
        Flux::modal('blueprint-form')->show();
    }

    public function edit(int $id): void
    {
        $blueprint = Blueprint::findOrFail($id);

        $this->editingId = $blueprint->id;
        $this->namespace = (string) $blueprint->namespace;
        $this->handle = $blueprint->handle;

        $this->tabs = collect($blueprint->data['tabs'] ?? [])
            ->map(fn (array $tab): array => [
                'handle' => $tab['handle'] ?? '',
                'display' => $tab['display'] ?? '',
                'sections' => collect($tab['sections'] ?? [])
                    ->map(fn (array $section): array => ['handle' => $section['handle'] ?? '', 'display' => $section['display'] ?? ''])
                    ->all(),
            ])
            ->all();

        if ($this->tabs === [] || ($this->tabs[0]['handle'] ?? null) !== 'main') {
            array_unshift($this->tabs, ['handle' => 'main', 'display' => 'Main', 'sections' => []]);
        }

        $this->editingTabIndex = null;
        $this->editingSection = null;

        $this->fields = collect($blueprint->data['fields'] ?? [])
            ->map(fn (array $field): array => $this->mapFieldFromStorage($field))
            ->all();

        if ($this->fields === []) {
            $this->addField();
        }

        Flux::modal('blueprint-form')->show();
    }

    public function addTab(): void
    {
        $this->tabs[] = ['handle' => '', 'display' => '', 'sections' => []];
        $this->editingTabIndex = count($this->tabs) - 1;
    }

    /**
     * Removing a tab reassigns any top-level fields in it back to "main" so
     * they're never orphaned under a tab that no longer exists.
     */
    public function removeTab(int $index): void
    {
        if ($index === 0) {
            return;
        }

        $handle = $this->tabs[$index]['handle'] ?? null;

        foreach ($this->fields as &$field) {
            if (($field['tab'] ?? 'main') === $handle) {
                $field['tab'] = 'main';
                $field['section'] = '';
            }
        }
        unset($field);

        unset($this->tabs[$index]);
        $this->tabs = array_values($this->tabs);

        if ($this->editingTabIndex === $index) {
            $this->editingTabIndex = null;
        }

        if ($this->editingSection !== null && $this->editingSection[0] === $index) {
            $this->editingSection = null;
        }
    }

    public function addSection(int $tabIndex): void
    {
        $this->tabs[$tabIndex]['sections'][] = ['handle' => '', 'display' => ''];
        $this->editingSection = [$tabIndex, count($this->tabs[$tabIndex]['sections']) - 1];
    }

    /**
     * Removing a section ungroups any fields in it (within that tab) back
     * to no section, rather than orphaning them.
     */
    public function removeSection(int $tabIndex, int $sectionIndex): void
    {
        $tabHandle = $this->tabs[$tabIndex]['handle'] ?? null;
        $sectionHandle = $this->tabs[$tabIndex]['sections'][$sectionIndex]['handle'] ?? null;

        foreach ($this->fields as &$field) {
            if (($field['tab'] ?? 'main') === $tabHandle && ($field['section'] ?? '') === $sectionHandle) {
                $field['section'] = '';
            }
        }
        unset($field);

        unset($this->tabs[$tabIndex]['sections'][$sectionIndex]);
        $this->tabs[$tabIndex]['sections'] = array_values($this->tabs[$tabIndex]['sections']);

        if ($this->editingSection === [$tabIndex, $sectionIndex]) {
            $this->editingSection = null;
        }
    }

    protected function mapFieldFromStorage(array $field): array
    {
        return [
            '_uid' => (string) Str::uuid(),
            'handle' => $field['handle'] ?? '',
            'display' => $field['display'] ?? '',
            'type' => $field['type'] ?? 'text',
            'required' => (bool) ($field['required'] ?? false),
            'localizable' => (bool) ($field['localizable'] ?? false),
            'options' => is_array($field['options'] ?? null) ? implode(', ', $field['options']) : (string) ($field['options'] ?? ''),
            'placeholder' => $field['placeholder'] ?? '',
            'default' => is_array($field['default'] ?? null) ? implode(', ', $field['default']) : (string) ($field['default'] ?? ''),
            'validation' => $field['validation'] ?? '',
            'instructions' => $field['instructions'] ?? '',
            'sometimes' => (bool) ($field['validate_sometimes'] ?? false),
            'condition_mode' => $field['conditions']['mode'] ?? 'always',
            'condition_field' => $field['conditions']['field'] ?? '',
            'condition_operator' => $field['conditions']['operator'] ?? 'equals',
            'condition_value' => $field['conditions']['value'] ?? '',
            'min_items' => isset($field['min_items']) ? (string) $field['min_items'] : '',
            'max_items' => isset($field['max_items']) ? (string) $field['max_items'] : '',
            'tab' => $field['tab'] ?? 'main',
            'section' => $field['section'] ?? '',
            'replicator_fields' => collect($field['replicator_fields'] ?? [])
                ->map(fn (array $sub): array => $this->mapFieldFromStorage($sub))
                ->all(),
        ];
    }

    public function addField(string $tab = 'main', string $section = ''): void
    {
        $this->fields[] = array_merge($this->fieldDefaults(), ['tab' => $tab, 'section' => $section, '_uid' => (string) Str::uuid()]);
    }

    /**
     * Add a sub-field to the replicator located at $path (a list of
     * indexes, e.g. [0] for the top-level field, [0, 2] for the 3rd
     * sub-field's own replicator, and so on to any depth).
     */
    public function addReplicatorField(array $path): void
    {
        $field = &$this->fieldAt($path);
        $field['replicator_fields'][] = array_merge($this->fieldDefaults(), ['_uid' => (string) Str::uuid()]);
    }

    public function removeField(array $path): void
    {
        $index = array_pop($path);
        $siblings = &$this->siblingsAt($path);
        unset($siblings[$index]);
        $siblings = array_values($siblings);
    }

    /**
     * Drag-and-drop reorder handler for wire:sort (see field-editor.blade.php
     * and the tab/section field lists in blueprint-manager.blade.php). $id
     * is the dragged field's stable "_uid" (matching its wire:key, so
     * Livewire's morph keeps the same DOM/Alpine state — e.g. a field's
     * collapsed/expanded toggle — across the reorder instead of resetting
     * it), and $position is its new zero-based position within that list.
     * $groupId is JSON describing which list: {"tab":...,"section":...} for
     * a top-level tab/section group, or {"parentPath":[...]} for a
     * replicator's fields.
     */
    public function reorderFields(string $id, int $position, string $groupId): void
    {
        $group = json_decode($groupId, true) ?? [];
        $parentPath = $group['parentPath'] ?? [];

        if ($parentPath === []) {
            $index = $this->findFieldIndexByUid($this->fields, $id);

            if ($index === null) {
                return;
            }

            $this->reorderTopLevelFields($index, $position, $group['tab'] ?? 'main', $group['section'] ?? '');

            return;
        }

        $siblings = &$this->siblingsAt($parentPath);
        $oldIndex = $this->findFieldIndexByUid($siblings, $id);

        if ($oldIndex === null) {
            return;
        }

        $values = array_values($siblings);
        $moved = array_splice($values, $oldIndex, 1)[0];
        array_splice($values, $position, 0, [$moved]);
        $siblings = $values;
    }

    protected function findFieldIndexByUid(array $fields, string $uid): ?int
    {
        foreach ($fields as $index => $field) {
            if (($field['_uid'] ?? null) === $uid) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Reorder within a tab/section group without disturbing other groups'
     * positions in the flat $fields array — the absolute index slots a
     * group occupies stay fixed, only the values assigned to them change,
     * mirroring moveTopLevelField()'s swap-within-group approach.
     */
    protected function reorderTopLevelFields(int $index, int $position, string $tab, string $section): void
    {
        if (! array_key_exists($index, $this->fields)) {
            return;
        }

        // Dropping into a different tab/section moves the field there —
        // update it before computing the group, so a cross-group drag
        // relocates the field instead of being silently ignored.
        $this->fields[$index]['tab'] = $tab;
        $this->fields[$index]['section'] = $section;

        $groupIndexes = array_values(array_filter(
            array_keys($this->fields),
            fn (int $i): bool => ($this->fields[$i]['tab'] ?? 'main') === $tab && ($this->fields[$i]['section'] ?? '') === $section
        ));

        $oldPosition = array_search($index, $groupIndexes, true);

        if ($oldPosition === false) {
            return;
        }

        $orderedValues = array_map(fn (int $i): array => $this->fields[$i], $groupIndexes);
        $moved = array_splice($orderedValues, $oldPosition, 1)[0];
        $position = max(0, min($position, count($orderedValues)));
        array_splice($orderedValues, $position, 0, [$moved]);

        foreach ($groupIndexes as $slotPosition => $absoluteIndex) {
            $this->fields[$absoluteIndex] = $orderedValues[$slotPosition];
        }
    }

    public function moveFieldUp(array $path): void
    {
        if (count($path) === 1) {
            $this->moveTopLevelField($path[0], -1);

            return;
        }

        $index = array_pop($path);

        if ($index === 0) {
            return;
        }

        $this->swapFieldsAt($path, $index, $index - 1);
    }

    public function moveFieldDown(array $path): void
    {
        if (count($path) === 1) {
            $this->moveTopLevelField($path[0], 1);

            return;
        }

        $index = array_pop($path);
        $siblings = &$this->siblingsAt($path);

        if ($index === count($siblings) - 1) {
            return;
        }

        $this->swapFieldsAt($path, $index, $index + 1);
    }

    /**
     * Top-level fields are grouped into tabs (and, within a tab, sections)
     * in the UI, so "move up/down" there reorders within the field's own
     * group — swapping with the flat array's adjacent index would
     * otherwise jump across tabs or sections.
     */
    protected function moveTopLevelField(int $index, int $direction): void
    {
        $tab = $this->fields[$index]['tab'] ?? 'main';
        $section = $this->fields[$index]['section'] ?? '';

        $sameGroupIndexes = array_values(array_filter(
            array_keys($this->fields),
            fn (int $i): bool => ($this->fields[$i]['tab'] ?? 'main') === $tab && ($this->fields[$i]['section'] ?? '') === $section
        ));

        $position = array_search($index, $sameGroupIndexes, true);
        $swapPosition = $position + $direction;

        if ($swapPosition < 0 || $swapPosition >= count($sameGroupIndexes)) {
            return;
        }

        $otherIndex = $sameGroupIndexes[$swapPosition];
        [$this->fields[$index], $this->fields[$otherIndex]] = [$this->fields[$otherIndex], $this->fields[$index]];
    }

    /**
     * Reference to the field at $path, walking into "replicator_fields" one
     * level per remaining path segment after the first.
     */
    protected function &fieldAt(array $path): array
    {
        $index = array_shift($path);
        $field = &$this->fields[$index];

        foreach ($path as $subIndex) {
            $field = &$field['replicator_fields'][$subIndex];
        }

        return $field;
    }

    /**
     * Reference to the sibling array a field at $parentPath lives in — the
     * root $fields array itself when $parentPath is empty, otherwise that
     * field's "replicator_fields".
     */
    protected function &siblingsAt(array $parentPath): array
    {
        if ($parentPath === []) {
            return $this->fields;
        }

        $field = &$this->fieldAt($parentPath);

        return $field['replicator_fields'];
    }

    protected function swapFieldsAt(array $parentPath, int $a, int $b): void
    {
        $siblings = &$this->siblingsAt($parentPath);
        [$siblings[$a], $siblings[$b]] = [$siblings[$b], $siblings[$a]];
    }

    /**
     * Reset a field's default value whenever its type changes, since the
     * default-value control (text / true-false radio / options select)
     * differs per type and a leftover value from the previous type would
     * no longer make sense. Works at any nesting depth.
     */
    public function updated(string $name): void
    {
        if (preg_match('/^fields\.\d+(?:\.replicator_fields\.\d+)*\.type$/', $name)) {
            $field = &$this->fieldAt($this->extractPath($name, '.type'));
            $type = $field['type'];

            $field['default'] = '';

            if (in_array($type, ['toggle', 'date', 'replicator', 'assets'], true)) {
                $field['placeholder'] = '';
            }

            if ($type !== 'replicator') {
                $field['replicator_fields'] = [];
            }

            if (! in_array($type, [...self::OPTION_BASED_TYPES, 'assets', 'link'], true)) {
                $field['options'] = '';
            }

            return;
        }

        if (preg_match('/^fields\.(\d+)\.tab$/', $name, $matches)) {
            $this->fields[(int) $matches[1]]['section'] = '';

            return;
        }

        if (preg_match('/^fields\.\d+(?:\.replicator_fields\.\d+)*\.condition_mode$/', $name)) {
            $field = &$this->fieldAt($this->extractPath($name, '.condition_mode'));

            if ($field['condition_mode'] === 'always') {
                $field['condition_field'] = '';
                $field['condition_operator'] = 'equals';
                $field['condition_value'] = '';
            }
        }
    }

    /**
     * Turn a dotted property name like
     * "fields.0.replicator_fields.2.replicator_fields.1.type" into the
     * index path [0, 2, 1] used by fieldAt()/siblingsAt().
     */
    protected function extractPath(string $name, string $suffix): array
    {
        $trimmed = substr($name, 0, -strlen($suffix));

        return array_values(array_map(
            'intval',
            array_filter(explode('.', $trimmed), fn (string $segment): bool => ctype_digit($segment))
        ));
    }

    protected function ruleStringError(string $ruleString): ?string
    {
        if ($ruleString === '') {
            return null;
        }

        try {
            ValidatorFacade::make(['value' => 'sample'], ['value' => $ruleString])->passes();

            return null;
        } catch (Throwable) {
            return __('Unknown or invalid Laravel validation rule.');
        }
    }

    /**
     * When a field is set to "show when" / "hide when", it must point at
     * another field (by handle) within its own scope — the blueprint for a
     * top-level field, or its parent replicator's own sub-fields for a
     * nested one — with an operator and a comparison value, otherwise the
     * condition can never evaluate.
     *
     * @param  array<int, string>  $scopeHandles
     * @return array<string, string>
     */
    protected function conditionErrors(array $field, array $scopeHandles): array
    {
        if ($field['condition_mode'] === 'always') {
            return [];
        }

        $errors = [];

        if (($field['condition_field'] ?? '') === '') {
            $errors['condition_field'] = __('Select a field to base this condition on.');
        } elseif ($field['condition_field'] === $field['handle']) {
            $errors['condition_field'] = __('A field cannot depend on its own condition.');
        } elseif (! in_array($field['condition_field'], $scopeHandles, true)) {
            $errors['condition_field'] = __('That field does not exist in this scope.');
        }

        if (($field['condition_operator'] ?? '') === '') {
            $errors['condition_operator'] = __('Select an operator for this condition.');
        }

        if (($field['condition_value'] ?? '') === '') {
            $errors['condition_value'] = __('Enter a value to compare against.');
        }

        return $errors;
    }

    /**
     * Fill in any missing field keys with their addField() defaults, so a
     * partially-built $fields array (e.g. from a bulk ->set() call) is still
     * safe to validate and render instead of throwing on undefined keys.
     */
    protected function fieldDefaults(): array
    {
        return [
            'handle' => '', 'display' => '', 'type' => 'text', 'required' => false, 'localizable' => false, 'options' => '',
            'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false,
            'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => '',
            'min_items' => '', 'max_items' => '', 'tab' => 'main', 'section' => '',
            'replicator_fields' => [],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeFieldsTree(array $fields): array
    {
        return array_map(function (array $field): array {
            $field = array_merge($this->fieldDefaults(), $field);
            $field['replicator_fields'] = $this->normalizeFieldsTree($field['replicator_fields'] ?? []);

            return $field;
        }, $fields);
    }

    protected function fieldValidationRules(): array
    {
        return [
            'handle' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::notIn(['title'])],
            'display' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(self::FIELDTYPES))],
            'required' => ['boolean'],
            'localizable' => ['boolean'],
            'options' => ['nullable', 'string'],
            'placeholder' => ['nullable', 'string', 'max:255'],
            'default' => ['nullable', 'string', 'max:255'],
            'validation' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_:,|.\-]*$/'],
            'instructions' => ['nullable', 'string', 'max:1000'],
            'sometimes' => ['boolean'],
            'condition_mode' => ['required', Rule::in(self::CONDITION_MODES)],
            'condition_field' => ['nullable', 'string', 'max:255'],
            'condition_operator' => ['nullable', Rule::in(array_keys(self::CONDITION_OPERATORS))],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'min_items' => ['nullable', 'integer', 'min:0'],
            'max_items' => ['nullable', 'integer', 'min:0'],
            'tab' => ['nullable', 'string'],
            'section' => ['nullable', 'string'],
        ];
    }

    /**
     * Validate a level of the field tree (and recurse into every
     * replicator's own sub-fields), returning dotted-path errors keyed the
     * same way Livewire's own $this->validate() would. Recursion is what
     * lets a replicator contain a replicator to any depth — a fixed set of
     * Laravel wildcard rules can't express an unknown nesting depth.
     *
     * @return array<string, string>
     */
    protected function validateFieldsTree(array $fields, string $prefix, array $tabHandles = [], array $sectionHandlesByTab = []): array
    {
        $errors = [];
        $handles = array_column($fields, 'handle');

        if (count($handles) !== count(array_unique($handles))) {
            $errors[$prefix] = __('Field handles must be unique within this level.');
        }

        foreach ($fields as $index => $field) {
            $errorKey = "{$prefix}.{$index}";

            $validator = ValidatorFacade::make($field, $this->fieldValidationRules(), [
                'handle.regex' => __('Field handle must be lowercase letters, numbers and underscores, starting with a letter.'),
                'handle.not_in' => __('"title" is a reserved handle — it is already a default field on every entry.'),
                'validation.regex' => __('Validation rules may only contain letters, numbers and : , | . _ -'),
            ]);

            foreach ($validator->errors()->toArray() as $attribute => $messages) {
                $errors["{$errorKey}.{$attribute}"] = $messages[0];
            }

            if (
                ! isset($errors["{$errorKey}.default"])
                && ($field['default'] ?? '') !== ''
                && $field['type'] === 'date'
                && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $field['default'])
            ) {
                $errors["{$errorKey}.default"] = __('Default value must be a valid date (YYYY-MM-DD).');
            }

            if (! isset($errors["{$errorKey}.validation"]) && $message = $this->ruleStringError($field['validation'] ?? '')) {
                $errors["{$errorKey}.validation"] = $message;
            }

            if (! isset($errors["{$errorKey}.condition_mode"])) {
                foreach ($this->conditionErrors($field, $handles) as $key => $message) {
                    $errors["{$errorKey}.{$key}"] = $message;
                }
            }

            if ($field['type'] === 'assets' && ! isset($errors["{$errorKey}.options"])) {
                $container = trim($field['options'] ?? '');

                if ($container === '') {
                    $errors["{$errorKey}.options"] = __('Select an asset container.');
                } elseif (! in_array($container, $this->availableAssetContainers->pluck('handle')->all(), true)) {
                    $errors["{$errorKey}.options"] = __('That asset container does not exist.');
                }
            }

            if ($field['type'] === 'link' && ! isset($errors["{$errorKey}.options"])) {
                $validHandles = $this->availableCollections->pluck('handle')->all();

                foreach (explode(',', $field['options'] ?? '') as $collectionHandle) {
                    $collectionHandle = trim($collectionHandle);

                    if ($collectionHandle !== '' && ! in_array($collectionHandle, $validHandles, true)) {
                        $errors["{$errorKey}.options"] = __('":handle" is not a valid collection.', ['handle' => $collectionHandle]);
                        break;
                    }
                }
            }

            if ($tabHandles !== [] && ! in_array($field['tab'] ?? 'main', $tabHandles, true)) {
                $errors["{$errorKey}.tab"] = __('That tab does not exist.');
            } elseif (
                $tabHandles !== []
                && ($field['section'] ?? '') !== ''
                && ! in_array($field['section'], $sectionHandlesByTab[$field['tab'] ?? 'main'] ?? [], true)
            ) {
                $errors["{$errorKey}.section"] = __('That section does not exist in this tab.');
            }

            if (
                in_array($field['type'], ['assets', 'checkbox'], true)
                && ! isset($errors["{$errorKey}.max_items"])
                && ($field['min_items'] ?? '') !== ''
                && ($field['max_items'] ?? '') !== ''
                && (int) $field['max_items'] < (int) $field['min_items']
            ) {
                $errors["{$errorKey}.max_items"] = __('Maximum selections must be greater than or equal to minimum selections.');
            }

            if ($field['type'] === 'replicator') {
                if (empty($field['replicator_fields'])) {
                    $errors["{$errorKey}.replicator_fields"] = __('Add at least one field to the replicator.');
                } else {
                    $errors = array_merge($errors, $this->validateFieldsTree($field['replicator_fields'], "{$errorKey}.replicator_fields"));
                }
            }
        }

        return $errors;
    }

    protected function normalizeFieldForStorage(array $field, bool $topLevel = true): array
    {
        $normalized = [
            'handle' => $field['handle'],
            'display' => $field['display'] ?: Str::headline($field['handle']),
            'type' => $field['type'],
            'required' => (bool) $field['required'],
            'localizable' => (bool) ($field['localizable'] ?? false),
            'placeholder' => in_array($field['type'], ['toggle', 'date', 'replicator', 'assets'], true)
                ? null
                : (($field['placeholder'] ?? '') ?: null),
            'default' => in_array($field['type'], ['replicator', 'assets', 'link'], true) ? null : (($field['default'] ?? '') ?: null),
            'validation' => ($field['validation'] ?? '') ?: null,
            'instructions' => ($field['instructions'] ?? '') ?: null,
            'validate_sometimes' => (bool) ($field['sometimes'] ?? false),
            'conditions' => $field['condition_mode'] !== 'always' ? [
                'mode' => $field['condition_mode'],
                'field' => $field['condition_field'],
                'operator' => $field['condition_operator'],
                'value' => $field['condition_value'],
            ] : null,
        ];

        if (in_array($field['type'], self::OPTION_BASED_TYPES, true)) {
            $normalized['options'] = collect(explode(',', $field['options'] ?? ''))
                ->map(fn (string $option): string => trim($option))
                ->filter()
                ->values()
                ->all();
        }

        if ($field['type'] === 'checkbox') {
            $normalized['default'] = collect(explode(',', $field['default'] ?? ''))
                ->map(fn (string $value): string => trim($value))
                ->filter()
                ->values()
                ->all();
        }

        if ($field['type'] === 'assets') {
            $normalized['options'] = trim($field['options'] ?? '') ?: null;
        }

        if ($field['type'] === 'link') {
            $normalized['options'] = collect(explode(',', $field['options'] ?? ''))
                ->map(fn (string $collectionHandle): string => trim($collectionHandle))
                ->filter()
                ->values()
                ->all();
        }

        if (in_array($field['type'], ['assets', 'checkbox'], true)) {
            $normalized['min_items'] = ($field['min_items'] ?? '') !== '' ? (int) $field['min_items'] : null;
            $normalized['max_items'] = ($field['max_items'] ?? '') !== '' ? (int) $field['max_items'] : null;
        }

        if ($field['type'] === 'replicator') {
            $normalized['replicator_fields'] = array_map(
                fn (array $sub): array => $this->normalizeFieldForStorage($sub, topLevel: false),
                $field['replicator_fields'] ?? []
            );
        }

        if ($topLevel) {
            $normalized['tab'] = $field['tab'] ?? 'main';
            $normalized['section'] = ($field['section'] ?? '') ?: null;
        }

        return $normalized;
    }

    /**
     * Validate $this->tabs (forcing index 0 to always be the reserved
     * "main" tab, not user-editable) and each tab's own sections, requiring
     * unique, valid handles throughout.
     *
     * @return array{0: array<string, string>, 1: array<int, string>, 2: array<string, array<int, string>>}
     */
    protected function validateTabs(): array
    {
        $this->tabs[0]['handle'] = 'main';
        $this->tabs[0]['display'] = 'Main';

        $errors = [];
        $tabHandles = ['main'];
        $sectionHandlesByTab = [];

        foreach ($this->tabs as $index => $tab) {
            if ($index !== 0) {
                $tabHandle = trim($tab['handle'] ?? '');

                if ($tabHandle === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $tabHandle) || $tabHandle === 'main') {
                    $errors["tabs.{$index}.handle"] = __('Tab handle must be lowercase letters, numbers and underscores, starting with a letter, and not "main".');
                } elseif (in_array($tabHandle, $tabHandles, true)) {
                    $errors["tabs.{$index}.handle"] = __('Tab handles must be unique.');
                } else {
                    $tabHandles[] = $tabHandle;
                }
            } else {
                $tabHandle = 'main';
            }

            $sectionHandles = [];

            foreach ($tab['sections'] ?? [] as $sectionIndex => $section) {
                $sectionHandle = trim($section['handle'] ?? '');

                if ($sectionHandle === '' || ! preg_match('/^[a-z][a-z0-9_]*$/', $sectionHandle)) {
                    $errors["tabs.{$index}.sections.{$sectionIndex}.handle"] = __('Section handle must be lowercase letters, numbers and underscores, starting with a letter.');
                } elseif (in_array($sectionHandle, $sectionHandles, true)) {
                    $errors["tabs.{$index}.sections.{$sectionIndex}.handle"] = __('Section handles must be unique within a tab.');
                } else {
                    $sectionHandles[] = $sectionHandle;
                }
            }

            $sectionHandlesByTab[$tabHandle] = $sectionHandles;
        }

        return [$errors, $tabHandles, $sectionHandlesByTab];
    }

    public function save(): void
    {
        $this->fields = $this->normalizeFieldsTree($this->fields);

        $validated = $this->validate([
            'namespace' => [
                'nullable', 'string', 'max:255',
                Rule::in($this->availableNamespaces->pluck('value')->all()),
            ],
            'handle' => [
                'required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('blueprints', 'handle')
                    ->where('namespace', $this->namespace ?: null)
                    ->ignore($this->editingId),
            ],
        ]);

        [$tabErrors, $tabHandles, $sectionHandlesByTab] = $this->validateTabs();

        $fieldErrors = $this->fields === [] ? ['fields' => __('Add at least one field.')] : [];
        $fieldErrors = array_merge($tabErrors, $fieldErrors, $this->validateFieldsTree($this->fields, 'fields', $tabHandles, $sectionHandlesByTab));

        if ($fieldErrors !== []) {
            throw ValidationException::withMessages($fieldErrors);
        }

        $data = [
            'tabs' => array_map(fn (array $tab): array => [
                'handle' => $tab['handle'],
                'display' => $tab['display'] ?: Str::headline($tab['handle']),
                'sections' => array_map(fn (array $section): array => [
                    'handle' => $section['handle'],
                    'display' => $section['display'] ?: Str::headline($section['handle']),
                ], $tab['sections'] ?? []),
            ], $this->tabs),
            'fields' => array_map(fn (array $field): array => $this->normalizeFieldForStorage($field), $this->fields),
        ];

        Blueprint::updateOrCreate(['id' => $this->editingId], [
            'namespace' => $this->namespace ?: null,
            'handle' => $validated['handle'],
            'data' => $data,
        ]);

        Flux::modal('blueprint-form')->close();
        Flux::toast(variant: 'success', text: __('Blueprint saved.'));

        unset($this->blueprints);
    }

    public function delete(int $id): void
    {
        Blueprint::findOrFail($id)->delete();

        Flux::toast(variant: 'success', text: __('Blueprint deleted.'));

        unset($this->blueprints);
    }

    public function render(): View
    {
        return view('blueprints::livewire.blueprint-manager', [
            'fieldtypes' => self::FIELDTYPES,
            'conditionOperators' => self::CONDITION_OPERATORS,
            'assetContainers' => $this->availableAssetContainers,
            'linkableCollections' => $this->availableCollections,
        ]);
    }
}
