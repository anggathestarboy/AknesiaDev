@php
    $field = array_merge(
        [
            "_uid" => implode("-", $path),
            "handle" => "",
            "display" => "",
            "type" => "text",
            "required" => false,
            "localizable" => false,
            "options" => "",
            "placeholder" => "",
            "default" => "",
            "validation" => "",
            "instructions" => "",
            "sometimes" => false,
            "condition_mode" => "always",
            "condition_field" => "",
            "condition_operator" => "equals",
            "condition_value" => "",
            "tab" => "main",
            "section" => "",
            "replicator_fields" => [],
        ],
        $field,
    );
    $pathJson = json_encode($path);
    $isTopLevelField = count($path) === 1;
    $fieldTabSections = collect($tabs ?? [])->firstWhere("handle", $field["tab"])["sections"] ?? [];
@endphp

<div wire:key="field-{{ $field['_uid'] }}" wire:sort:item="{{ $field['_uid'] }}" class="rounded-lg border border-gray-200 p-2 dark:border-zinc-700 bg-gray-100" x-data="{ collapsed: false }" x-on:collapse-all-fields.window="collapsed = true" x-on:expand-all-fields.window="collapsed = false">
    <div class="flex items-start justify-between bg-gray-100 rounded-lg p-1 w-full">
        <div class="col-span-1 flex items-center justify-end gap-1">
            <span wire:sort:handle class="flex cursor-grab items-center px-1 text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:hover:text-zinc-300" title="{{ __('Drag to reorder') }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                    <circle cx="5" cy="3" r="1.3" />
                    <circle cx="11" cy="3" r="1.3" />
                    <circle cx="5" cy="8" r="1.3" />
                    <circle cx="11" cy="8" r="1.3" />
                    <circle cx="5" cy="13" r="1.3" />
                    <circle cx="11" cy="13" r="1.3" />
                </svg>
            </span>
            <flux:button size="sm" variant="ghost" icon="trash" type="button" class="cursor-pointer" wire:click="removeField({{ $pathJson }})" />
        </div>

        <flux:text class="flex-1 self-center truncate px-2 text-sm font-medium">
            {{ $field["display"] ?: $field["handle"] ?: __("Untitled field") }}
            <span class="text-zinc-400">({{ $fieldtypes[$field["type"]] ?? $field["type"] }})</span>
        </flux:text>

        <div class="">
            <flux:button size="sm" variant="ghost" icon="minus" type="button" class="cursor-pointer" x-show="!collapsed" x-on:click="collapsed = !collapsed" />
            <flux:button size="sm" variant="ghost" icon="plus" type="button" class="cursor-pointer" x-show="collapsed" x-cloak x-on:click="collapsed = !collapsed" />
        </div>

        <div class="">
            @if ($isTopLevelField && (count($tabs) > 1 || $fieldTabSections !== []))
                <div class="flex gap-2">
                    @if (count($tabs) > 1)
                        <flux:select wire:model.live="{{ $prefix }}.tab" size="sm">
                            @foreach ($tabs as $tabOption)
                                <flux:select.option value="{{ $tabOption['handle'] }}">{{ $tabOption["display"] ?: $tabOption["handle"] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    @if ($fieldTabSections !== [])
                        <flux:select wire:model="{{ $prefix }}.section" placeholder="{{ __('No section') }}" size="sm">
                            @foreach ($fieldTabSections as $sectionOption)
                                <flux:select.option value="{{ $sectionOption['handle'] }}">{{ $sectionOption["display"] ?: $sectionOption["handle"] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div x-show="!collapsed" x-cloak>
        <div class="grid grid-cols-12 gap-3 mt-6">
            <div class="col-span-3">
                <flux:input wire:model="{{ $prefix }}.display" :label="__('Label')" placeholder="Title" size="sm" x-ref="labelInput" />
            </div>
            <div class="col-span-3">
                <div class="flex items-end gap-1">
                    <div class="flex-1">
                        <flux:input wire:model="{{ $prefix }}.handle" :label="__('Handle')" placeholder="title" size="sm" x-ref="handleInput" />
                    </div>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="arrow-path"
                        type="button"
                        class="cursor-pointer"
                        title="{{ __('Regenerate from label') }}"
                        x-on:click="
                            const handleEl = $refs.handleInput.tagName === 'INPUT' ? $refs.handleInput : $refs.handleInput.querySelector('input');
                            const labelEl = $refs.labelInput.tagName === 'INPUT' ? $refs.labelInput : $refs.labelInput.querySelector('input');
                            handleEl.value = labelEl.value
                                .toLowerCase()
                                .replace(/[^a-z\s_]/g, '')
                                .trim()
                                .replace(/\s+/g, '_')
                                .replace(/_+/g, '_');
                            handleEl.dispatchEvent(new Event('input'));
                        "
                    />
                </div>
            </div>
            <div class="col-span-3">
                <flux:select wire:model.live="{{ $prefix }}.type" :label="__('Type')" size="sm">
                    @foreach ($fieldtypes as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="col-span-2 flex flex-wrap items-center gap-2 justify-between">
                <flux:checkbox wire:model="{{ $prefix }}.required" :label="__('Required')" />
                <flux:checkbox wire:model="{{ $prefix }}.sometimes" :label="__('Sometimes')" />
                <flux:checkbox wire:model="{{ $prefix }}.localizable" :label="__('Localizable')" />
            </div>
        </div>

        @if ($field["type"] === "select" || $field["type"] === "radio")
            <div class="mt-3">
                <flux:input wire:model.live="{{ $prefix }}.options" :label="__('Options (comma separated)')" placeholder="draft, published, archived" size="sm" />
            </div>
        @elseif ($field["type"] === "checkbox")
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <flux:input wire:model.live="{{ $prefix }}.options" :label="__('Options (comma separated)')" placeholder="draft, published, archived" size="sm" />
                </div>

                <div>
                    <flux:input type="number" min="0" wire:model="{{ $prefix }}.min_items" :label="__('Min selections')" placeholder="{{ __('No minimum') }}" size="sm" />
                    @error ("{$prefix}.min_items")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:input type="number" min="0" wire:model="{{ $prefix }}.max_items" :label="__('Max selections')" placeholder="{{ __('No maximum') }}" size="sm" />
                    @error ("{$prefix}.max_items")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            </div>
        @elseif ($field["type"] === "assets")
            <div class="mt-3 grid grid-cols-3 gap-3">
                <div class="col-span-1">
                    <flux:select wire:model="{{ $prefix }}.options" :label="__('Asset container')" placeholder="{{ __('Select a container') }}" size="sm">
                        @foreach ($assetContainers as $container)
                            <flux:select.option value="{{ $container->handle }}">{{ $container->title }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error ("{$prefix}.options")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:input type="number" min="0" wire:model="{{ $prefix }}.min_items" :label="__('Min items')" placeholder="{{ __('No minimum') }}" size="sm" />
                    @error ("{$prefix}.min_items")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>

                <div>
                    <flux:input type="number" min="0" wire:model="{{ $prefix }}.max_items" :label="__('Max items')" placeholder="{{ __('No maximum') }}" size="sm" />
                    @error ("{$prefix}.max_items")
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror
                </div>
            </div>
        @elseif ($field["type"] === "link")
            <div class="mt-3">
                <flux:input wire:model.live="{{ $prefix }}.options" :label="__('Allowed collections (comma separated, blank = any)')" placeholder="pages, blog" size="sm" />
                @error ("{$prefix}.options")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
                @if ($linkableCollections->isNotEmpty())
                    <flux:text class="mt-1 block text-xs text-zinc-500"> {{ __("Available: :handles", ["handles" => $linkableCollections->pluck("handle")->implode(", ")]) }} </flux:text>
                @endif
            </div>
        @endif

        <div class="mt-3 grid grid-cols-4 gap-3">
            @if ($field["type"] === "replicator" || $field["type"] === "assets" || $field["type"] === "link")
                <flux:input :label="__('Placeholder')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
                <flux:input :label="__('Default value')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
            @else
                @if ($field["type"] === "toggle" || $field["type"] === "date")
                    <flux:input :label="__('Placeholder')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
                @else
                    <flux:input wire:model="{{ $prefix }}.placeholder" :label="__('Placeholder')" placeholder="{{ __('e.g. Enter a title') }}" size="sm" />
                @endif

                @if ($field["type"] === "toggle")
                    <flux:radio.group wire:model="{{ $prefix }}.default" :label="__('Default value')">
                        <flux:radio value="true" label="{{ __('True') }}" />
                        <flux:radio value="false" label="{{ __('False') }}" />
                    </flux:radio.group>
                @elseif ($field["type"] === "select" || $field["type"] === "radio")
                    <flux:select wire:model="{{ $prefix }}.default" :label="__('Default value')" placeholder="{{ __('None') }}" size="sm">
                        @foreach (collect(explode(",", $field["options"] ?? ""))->map(fn(string $option): string => trim($option))->filter() as $option)
                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @elseif ($field["type"] === "checkbox")
                    <flux:input wire:model="{{ $prefix }}.default" :label="__('Default value(s)')" placeholder="{{ __('Comma separated, from the options above') }}" size="sm" />
                @elseif ($field["type"] === "date")
                    <flux:input type="date" wire:model="{{ $prefix }}.default" :label="__('Default value')" size="sm" />
                @else
                    <flux:input wire:model="{{ $prefix }}.default" :label="__('Default value')" size="sm" />
                @endif
            @endif

            <flux:input wire:model="{{ $prefix }}.validation" :label="__('Validation rules')" placeholder="max:255|email" size="sm" />
            <flux:input wire:model="{{ $prefix }}.instructions" :label="__('Instructions')" placeholder="{{ __('Help text shown to editors') }}" size="sm" />
        </div>

        <div class="mt-3 pt-3 border-t border-gray-300">
            <p class="text-xs">Only validate when this field is visible or submitted.</p>
        </div>

        <div class="mt-3 flex gap-3 items-center rounded-md bg-zinc-50 p-3 dark:bg-zinc-800/50">
            <flux:select wire:model.live="{{ $prefix }}.condition_mode" :label="__('Condition')" size="sm">
                <flux:select.option value="always">{{ __("Always show") }}</flux:select.option>
                <flux:select.option value="show">{{ __("Show when") }}</flux:select.option>
                <flux:select.option value="hide">{{ __("Hide when") }}</flux:select.option>
            </flux:select>

            @if (($field["condition_mode"] ?? "always") !== "always")
                <flux:select wire:model="{{ $prefix }}.condition_field" :label="__('Field')" placeholder="{{ __('Select a field') }}" size="sm">
                    @foreach ($siblings as $otherIndex => $otherField)
                        @continue ($otherIndex === $index || ($otherField["handle"] ?? "") === "")
                        <flux:select.option value="{{ $otherField['handle'] }}">{{ $otherField["handle"] }}</flux:select.option>
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

        @if ($field["type"] === "replicator")
            <div class="mt-3 space-y-2 rounded-md border border-dashed border-zinc-300 p-3 dark:border-zinc-600">
                <div class="flex items-center justify-between">
                    <flux:text class="text-xs font-medium text-zinc-500">{{ __("Replicator fields") }}</flux:text>
                    <flux:button size="sm" variant="ghost" type="button" wire:click="addReplicatorField({{ $pathJson }})">{{ __("Add field") }}</flux:button>
                </div>

                @error ("{$prefix}.replicator_fields")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div wire:sort="reorderFields" wire:sort:group="blueprint-fields-replicator-{{ implode('-', $path) }}" wire:sort:group-id="{{ json_encode(['parentPath' => $path]) }}" class="space-y-2">
                    @foreach ($field["replicator_fields"] ?? [] as $subIndex => $subField)
                        @include ("blueprints::livewire.partials.field-editor",
                            [
                                "field" => $subField,
                                "path" => [...$path, $subIndex],
                                "prefix" => "{$prefix}.replicator_fields.{$subIndex}",
                                "index" => $subIndex,
                                "isLast" => $subIndex === count($field["replicator_fields"]) - 1,
                                "siblings" => $field["replicator_fields"],
                                "fieldtypes" => $fieldtypes,
                                "conditionOperators" => $conditionOperators,
                                "assetContainers" => $assetContainers,
                                "linkableCollections" => $linkableCollections
                            ])
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
