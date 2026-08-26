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
     * @var array<int, array{
     *     handle: string, display: string, type: string, required: bool, options: string,
     *     placeholder: string, default: string, validation: string, instructions: string, sometimes: bool,
     *     condition_mode: string, condition_field: string, condition_operator: string, condition_value: string,
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
            ->map(fn (array $field): array => [
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
            ])
            ->all();

        if ($this->fields === []) {
            $this->addField();
        }

        Flux::modal('blueprint-form')->show();
    }

    public function addField(): void
    {
        $this->fields[] = $this->fieldDefaults();
    }

    public function removeField(int $index): void
    {
        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    /**
     * Reset a field's default value whenever its type changes, since the
     * default-value control (text / true-false radio / options select)
     * differs per type and a leftover value from the previous type would
     * no longer make sense.
     */
    public function updated(string $name): void
    {
        if (preg_match('/^fields\.(\d+)\.type$/', $name, $matches)) {
            $index = (int) $matches[1];
            $type = $this->fields[$index]['type'];

            $this->fields[$index]['default'] = '';

            if (in_array($type, ['toggle', 'date'], true)) {
                $this->fields[$index]['placeholder'] = '';
            }
        }

        if (preg_match('/^fields\.(\d+)\.condition_mode$/', $name, $matches)) {
            $index = (int) $matches[1];

            if ($this->fields[$index]['condition_mode'] === 'always') {
                $this->fields[$index]['condition_field'] = '';
                $this->fields[$index]['condition_operator'] = 'equals';
                $this->fields[$index]['condition_value'] = '';
            }
        }
    }

    public function moveFieldUp(int $index): void
    {
        if ($index === 0) {
            return;
        }

        $this->swapFields($index, $index - 1);
    }

    public function moveFieldDown(int $index): void
    {
        if ($index === count($this->fields) - 1) {
            return;
        }

        $this->swapFields($index, $index + 1);
    }

    protected function swapFields(int $a, int $b): void
    {
        [$this->fields[$a], $this->fields[$b]] = [$this->fields[$b], $this->fields[$a]];
    }

    /**
     * Sanity-check that a per-field Laravel validation rule string (e.g.
     * "required|max:255|email") is actually usable by Laravel's validator,
     * so a typo doesn't silently break entry forms later when this rule
     * gets applied to real entry data.
     */
    protected function assertValidRuleString(int $index, string $ruleString): void
    {
        if ($ruleString === '') {
            return;
        }

        try {
            ValidatorFacade::make(['value' => 'sample'], ['value' => $ruleString])->passes();
        } catch (Throwable) {
            throw ValidationException::withMessages([
                "fields.{$index}.validation" => __('Unknown or invalid Laravel validation rule.'),
            ]);
        }
    }

    /**
     * When a field is set to "show when" / "hide when", it must point at
     * another field (by handle) in the same blueprint, with an operator
     * and a comparison value — otherwise the condition can never evaluate.
     *
     * @param  array<int, string>  $allHandles
     */
    protected function assertValidCondition(int $index, array $field, array $allHandles): void
    {
        if ($field['condition_mode'] === 'always') {
            return;
        }

        $errors = [];

        if (($field['condition_field'] ?? '') === '') {
            $errors["fields.{$index}.condition_field"] = __('Select a field to base this condition on.');
        } elseif ($field['condition_field'] === $field['handle']) {
            $errors["fields.{$index}.condition_field"] = __('A field cannot depend on its own condition.');
        } elseif (! in_array($field['condition_field'], $allHandles, true)) {
            $errors["fields.{$index}.condition_field"] = __('That field does not exist in this blueprint.');
        }

        if (($field['condition_operator'] ?? '') === '') {
            $errors["fields.{$index}.condition_operator"] = __('Select an operator for this condition.');
        }

        if (($field['condition_value'] ?? '') === '') {
            $errors["fields.{$index}.condition_value"] = __('Enter a value to compare against.');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
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
        ];
    }

    public function save(): void
    {
        $this->fields = array_map(
            fn (array $field): array => array_merge($this->fieldDefaults(), $field),
            $this->fields
        );

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
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.handle' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/', Rule::notIn(['title'])],
            'fields.*.display' => ['nullable', 'string', 'max:255'],
            'fields.*.type' => ['required', Rule::in(array_keys(self::FIELDTYPES))],
            'fields.*.required' => ['boolean'],
            'fields.*.options' => ['nullable', 'string'],
            'fields.*.placeholder' => ['nullable', 'string', 'max:255'],
            'fields.*.default' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || $value === '') {
                    return;
                }

                preg_match('/^fields\.(\d+)\.default$/', $attribute, $matches);
                $type = $this->fields[(int) $matches[1]]['type'] ?? null;

                if ($type === 'date' && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $fail(__('Default value must be a valid date (YYYY-MM-DD).'));
                }
            }],
            'fields.*.validation' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_:,|.\-]*$/'],
            'fields.*.instructions' => ['nullable', 'string', 'max:1000'],
            'fields.*.sometimes' => ['boolean'],
            'fields.*.condition_mode' => ['required', Rule::in(self::CONDITION_MODES)],
            'fields.*.condition_field' => ['nullable', 'string', 'max:255'],
            'fields.*.condition_operator' => ['nullable', Rule::in(array_keys(self::CONDITION_OPERATORS))],
            'fields.*.condition_value' => ['nullable', 'string', 'max:255'],
        ], [
            'fields.*.handle.regex' => __('Field handle must be lowercase letters, numbers and underscores, starting with a letter.'),
            'fields.*.handle.not_in' => __('"title" is a reserved handle — it is already a default field on every entry.'),
            'fields.*.validation.regex' => __('Validation rules may only contain letters, numbers and : , | . _ -'),
        ]);

        $handles = array_column($validated['fields'], 'handle');

        if (count($handles) !== count(array_unique($handles))) {
            throw ValidationException::withMessages([
                'fields' => __('Field handles must be unique within the blueprint.'),
            ]);
        }

        foreach ($validated['fields'] as $index => $field) {
            $this->assertValidRuleString($index, $field['validation'] ?? '');
            $this->assertValidCondition($index, $field, $handles);
        }

        $data = [
            'fields' => array_map(function (array $field): array {
                $normalized = [
                    'handle' => $field['handle'],
                    'display' => $field['display'] ?: Str::headline($field['handle']),
                    'type' => $field['type'],
                    'required' => (bool) $field['required'],
                    'placeholder' => in_array($field['type'], ['toggle', 'date'], true)
                        ? null
                        : (($field['placeholder'] ?? '') ?: null),
                    'default' => ($field['default'] ?? '') ?: null,
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

                return $normalized;
            }, $validated['fields']),
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
