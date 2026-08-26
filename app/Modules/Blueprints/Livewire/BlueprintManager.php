<?php

namespace App\Modules\Blueprints\Livewire;

use App\Modules\Blueprints\Models\Blueprint;
use App\Modules\Collections\Models\Collection;
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
        'replicator' => 'Replicator',
    ];

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
     * A field tree: each field may itself contain a "replicator_fields"
     * array of the same shape, recursively, to any depth.
     *
     * @var array<int, array{
     *     handle: string, display: string, type: string, required: bool, options: string,
     *     placeholder: string, default: string, validation: string, instructions: string, sometimes: bool,
     *     condition_mode: string, condition_field: string, condition_operator: string, condition_value: string,
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
     * Blueprint namespaces are derived from existing collections (e.g. a
     * "blog" collection gives the namespace "collections.blog"), mirroring
     * how Statamic scopes blueprints to their collection.
     */
    #[Computed]
    public function availableNamespaces()
    {
        return Collection::orderBy('title')->get()
            ->map(fn (Collection $collection): array => [
                'value' => "collections.{$collection->handle}",
                'label' => "{$collection->title} (collections.{$collection->handle})",
            ]);
    }

    public function create(): void
    {
        $this->reset(['editingId', 'namespace', 'handle', 'fields']);
        $this->addField();
        Flux::modal('blueprint-form')->show();
    }

    public function edit(int $id): void
    {
        $blueprint = Blueprint::findOrFail($id);

        $this->editingId = $blueprint->id;
        $this->namespace = (string) $blueprint->namespace;
        $this->handle = $blueprint->handle;

        $this->fields = collect($blueprint->data['fields'] ?? [])
            ->map(fn (array $field): array => $this->mapFieldFromStorage($field))
            ->all();

        if ($this->fields === []) {
            $this->addField();
        }

        Flux::modal('blueprint-form')->show();
    }

    protected function mapFieldFromStorage(array $field): array
    {
        return [
            'handle' => $field['handle'] ?? '',
            'display' => $field['display'] ?? '',
            'type' => $field['type'] ?? 'text',
            'required' => (bool) ($field['required'] ?? false),
            'options' => implode(', ', $field['options'] ?? []),
            'placeholder' => $field['placeholder'] ?? '',
            'default' => $field['default'] ?? '',
            'validation' => $field['validation'] ?? '',
            'instructions' => $field['instructions'] ?? '',
            'sometimes' => (bool) ($field['validate_sometimes'] ?? false),
            'condition_mode' => $field['conditions']['mode'] ?? 'always',
            'condition_field' => $field['conditions']['field'] ?? '',
            'condition_operator' => $field['conditions']['operator'] ?? 'equals',
            'condition_value' => $field['conditions']['value'] ?? '',
            'replicator_fields' => collect($field['replicator_fields'] ?? [])
                ->map(fn (array $sub): array => $this->mapFieldFromStorage($sub))
                ->all(),
        ];
    }

    public function addField(): void
    {
        $this->fields[] = $this->fieldDefaults();
    }

    /**
     * Add a sub-field to the replicator located at $path (a list of
     * indexes, e.g. [0] for the top-level field, [0, 2] for the 3rd
     * sub-field's own replicator, and so on to any depth).
     */
    public function addReplicatorField(array $path): void
    {
        $field = &$this->fieldAt($path);
        $field['replicator_fields'][] = $this->fieldDefaults();
    }

    public function removeField(array $path): void
    {
        $index = array_pop($path);
        $siblings = &$this->siblingsAt($path);
        unset($siblings[$index]);
        $siblings = array_values($siblings);
    }

    public function moveFieldUp(array $path): void
    {
        $index = array_pop($path);

        if ($index === 0) {
            return;
        }

        $this->swapFieldsAt($path, $index, $index - 1);
    }

    public function moveFieldDown(array $path): void
    {
        $index = array_pop($path);
        $siblings = &$this->siblingsAt($path);

        if ($index === count($siblings) - 1) {
            return;
        }

        $this->swapFieldsAt($path, $index, $index + 1);
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

            if (in_array($type, ['toggle', 'date', 'replicator'], true)) {
                $field['placeholder'] = '';
            }

            if ($type !== 'replicator') {
                $field['replicator_fields'] = [];
            }

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
            'handle' => '', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '',
            'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false,
            'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => '',
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
    protected function validateFieldsTree(array $fields, string $prefix): array
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

    protected function normalizeFieldForStorage(array $field): array
    {
        $normalized = [
            'handle' => $field['handle'],
            'display' => $field['display'] ?: Str::headline($field['handle']),
            'type' => $field['type'],
            'required' => (bool) $field['required'],
            'placeholder' => in_array($field['type'], ['toggle', 'date', 'replicator'], true)
                ? null
                : (($field['placeholder'] ?? '') ?: null),
            'default' => $field['type'] === 'replicator' ? null : (($field['default'] ?? '') ?: null),
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

        if ($field['type'] === 'select') {
            $normalized['options'] = collect(explode(',', $field['options'] ?? ''))
                ->map(fn (string $option): string => trim($option))
                ->filter()
                ->values()
                ->all();
        }

        if ($field['type'] === 'replicator') {
            $normalized['replicator_fields'] = array_map(
                fn (array $sub): array => $this->normalizeFieldForStorage($sub),
                $field['replicator_fields'] ?? []
            );
        }

        return $normalized;
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
                'required', 'string', 'max:255',
                Rule::unique('blueprints', 'handle')
                    ->where('namespace', $this->namespace ?: null)
                    ->ignore($this->editingId),
            ],
        ]);

        $fieldErrors = $this->fields === [] ? ['fields' => __('Add at least one field.')] : [];
        $fieldErrors = array_merge($fieldErrors, $this->validateFieldsTree($this->fields, 'fields'));

        if ($fieldErrors !== []) {
            throw ValidationException::withMessages($fieldErrors);
        }

        $data = [
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
        ]);
    }
}
