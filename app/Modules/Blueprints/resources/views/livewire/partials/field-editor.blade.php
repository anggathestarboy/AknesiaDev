@php
    $field = array_merge([
        'handle' => '', 'display' => '', 'type' => 'text', 'required' => false, 'options' => '',
        'placeholder' => '', 'default' => '', 'validation' => '', 'instructions' => '', 'sometimes' => false,
        'condition_mode' => 'always', 'condition_field' => '', 'condition_operator' => 'equals', 'condition_value' => '',
        'replicator_fields' => [],
    ], $field);
    $pathJson = json_encode($path);
@endphp

<div wire:key="field-{{ implode('-', $path) }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-3">
            <flux:input wire:model="{{ $prefix }}.handle" :label="__('Handle')" placeholder="title" size="sm" />
        </div>
        <div class="col-span-3">
            <flux:input wire:model="{{ $prefix }}.display" :label="__('Label')" placeholder="Title" size="sm" />
        </div>
        <div class="col-span-3">
            <flux:select wire:model.live="{{ $prefix }}.type" :label="__('Type')" size="sm">
                @foreach ($fieldtypes as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="col-span-2 flex items-end pb-2">
            <flux:checkbox wire:model="{{ $prefix }}.required" :label="__('Required')" />
        </div>
        <div class="col-span-1 flex items-end justify-end gap-1 pb-1">
            <flux:button size="sm" variant="ghost" icon="chevron-up" type="button" wire:click="moveFieldUp({{ $pathJson }})" :disabled="$index === 0" />
            <flux:button size="sm" variant="ghost" icon="chevron-down" type="button" wire:click="moveFieldDown({{ $pathJson }})" :disabled="$isLast" />
            <flux:button size="sm" variant="ghost" icon="trash" type="button" wire:click="removeField({{ $pathJson }})" />
        </div>
    </div>

    @if ($field['type'] === 'select')
        <div class="mt-3">
            <flux:input wire:model.live="{{ $prefix }}.options" :label="__('Options (comma separated)')" placeholder="draft, published, archived" size="sm" />
        </div>
    @elseif ($field['type'] === 'assets')
        <div class="mt-3 grid grid-cols-3 gap-3">
            <div class="col-span-1">
                <flux:select wire:model="{{ $prefix }}.options" :label="__('Asset container')" placeholder="{{ __('Select a container') }}" size="sm">
                    @foreach ($assetContainers as $container)
                        <flux:select.option value="{{ $container->handle }}">{{ $container->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error("{$prefix}.options")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            </div>

            <div>
                <flux:input type="number" min="0" wire:model="{{ $prefix }}.min_items" :label="__('Min items')" placeholder="{{ __('No minimum') }}" size="sm" />
                @error("{$prefix}.min_items")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            </div>

            <div>
                <flux:input type="number" min="0" wire:model="{{ $prefix }}.max_items" :label="__('Max items')" placeholder="{{ __('No maximum') }}" size="sm" />
                @error("{$prefix}.max_items")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            </div>
        </div>
    @endif

    <div class="mt-3 grid grid-cols-4 gap-3">
        @if ($field['type'] === 'replicator' || $field['type'] === 'assets')
            <flux:input :label="__('Placeholder')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
            <flux:input :label="__('Default value')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
        @else
            @if ($field['type'] === 'toggle' || $field['type'] === 'date')
                <flux:input :label="__('Placeholder')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
            @else
                <flux:input wire:model="{{ $prefix }}.placeholder" :label="__('Placeholder')" placeholder="{{ __('e.g. Enter a title') }}" size="sm" />
            @endif

            @if ($field['type'] === 'toggle')
                <flux:radio.group wire:model="{{ $prefix }}.default" :label="__('Default value')">
                    <flux:radio value="true" label="{{ __('True') }}" />
                    <flux:radio value="false" label="{{ __('False') }}" />
                </flux:radio.group>
            @elseif ($field['type'] === 'select')
                <flux:select wire:model="{{ $prefix }}.default" :label="__('Default value')" placeholder="{{ __('None') }}" size="sm">
                    @foreach (collect(explode(',', $field['options'] ?? ''))->map(fn (string $option): string => trim($option))->filter() as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            @elseif ($field['type'] === 'date')
                <flux:input type="date" wire:model="{{ $prefix }}.default" :label="__('Default value')" size="sm" />
            @else
                <flux:input wire:model="{{ $prefix }}.default" :label="__('Default value')" size="sm" />
            @endif
        @endif

        <flux:input wire:model="{{ $prefix }}.validation" :label="__('Validation rules')" placeholder="max:255|email" size="sm" />
        <flux:input wire:model="{{ $prefix }}.instructions" :label="__('Instructions')" placeholder="{{ __('Help text shown to editors') }}" size="sm" />
    </div>

    <div class="mt-3">
        <flux:checkbox wire:model="{{ $prefix }}.sometimes" :label="__('Sometimes')" :description="__('Only validate when this field is visible or submitted.')" />
    </div>

    <div class="mt-3 grid grid-cols-4 items-end gap-3 rounded-md bg-zinc-50 p-3 dark:bg-zinc-800/50">
        <flux:select wire:model.live="{{ $prefix }}.condition_mode" :label="__('Condition')" size="sm">
            <flux:select.option value="always">{{ __('Always show') }}</flux:select.option>
            <flux:select.option value="show">{{ __('Show when') }}</flux:select.option>
            <flux:select.option value="hide">{{ __('Hide when') }}</flux:select.option>
        </flux:select>

        @if (($field['condition_mode'] ?? 'always') !== 'always')
            <flux:select wire:model="{{ $prefix }}.condition_field" :label="__('Field')" placeholder="{{ __('Select a field') }}" size="sm">
                @foreach ($siblings as $otherIndex => $otherField)
                    @continue($otherIndex === $index || ($otherField['handle'] ?? '') === '')
                    <flux:select.option value="{{ $otherField['handle'] }}">{{ $otherField['handle'] }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="{{ $prefix }}.condition_operator" :label="__('Operator')" size="sm">
                @foreach ($conditionOperators as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="{{ $prefix }}.condition_value" :label="__('Value')" size="sm" />
        @endif
    </div>

    @if ($field['type'] === 'replicator')
        <div class="mt-3 space-y-2 rounded-md border border-dashed border-zinc-300 p-3 dark:border-zinc-600">
            <div class="flex items-center justify-between">
                <flux:text class="text-xs font-medium text-zinc-500">{{ __('Replicator fields') }}</flux:text>
                <flux:button size="sm" variant="ghost" type="button" wire:click="addReplicatorField({{ $pathJson }})">{{ __('Add field') }}</flux:button>
            </div>

            @error("{$prefix}.replicator_fields")
                <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror

            @foreach ($field['replicator_fields'] ?? [] as $subIndex => $subField)
                @include('blueprints::livewire.partials.field-editor', [
                    'field' => $subField,
                    'path' => [...$path, $subIndex],
                    'prefix' => "{$prefix}.replicator_fields.{$subIndex}",
                    'index' => $subIndex,
                    'isLast' => $subIndex === count($field['replicator_fields']) - 1,
                    'siblings' => $field['replicator_fields'],
                    'fieldtypes' => $fieldtypes,
                    'conditionOperators' => $conditionOperators,
                    'assetContainers' => $assetContainers,
                ])
            @endforeach
        </div>
    @endif
</div>
