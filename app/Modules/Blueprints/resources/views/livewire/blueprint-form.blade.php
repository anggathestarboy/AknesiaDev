<section class="w-full max-w-6xl space-y-6" x-data="{ deleteFieldPath: null }">
    <div>
        <flux:link :href="route('blueprints.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Blueprints") }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $editingId ? __("Edit blueprint") : __("New blueprint") }}</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4">
            @if ($this->availableNamespaces->isEmpty())
                <div>
                    <flux:text class="text-sm font-medium">{{ __("Namespace") }}</flux:text>
                    <flux:text class="text-zinc-500">{{ __("No collections yet — create one in the Collections module first.") }}</flux:text>
                </div>
            @else
                <flux:select wire:model="namespace" :label="__('Namespace')" placeholder="{{ __('No namespace') }}">
                    @foreach ($this->availableNamespaces as $availableNamespace)
                        <flux:select.option value="{{ $availableNamespace['value'] }}">{{ $availableNamespace["label"] }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif

            <flux:input wire:model="handle" :label="__('Handle')" required />

            @if (empty($templates))
                <div>
                    <flux:text class="text-sm font-medium">{{ __("Template") }}</flux:text>
                    <flux:text class="text-zinc-500">{{ __("No templates yet — add a .blade.php file under resources/views/pages first.") }}</flux:text>
                </div>
            @else
                <flux:select wire:model="template" :label="__('Template')" placeholder="{{ __('No template') }}" :description="__('The Blade view used to render entries using this blueprint.')">
                    @foreach ($templates as $availableTemplate)
                        <flux:select.option value="{{ $availableTemplate }}">{{ $availableTemplate }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endif
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-end">
                <div class="flex gap-2">
                    <flux:button size="xs" variant="ghost" type="button" wire:click="$dispatch('collapse-all-fields')">{{ __("Collapse all") }}</flux:button>
                    <flux:button size="xs" variant="ghost" type="button" wire:click="$dispatch('expand-all-fields')">{{ __("Expand all") }}</flux:button>
                    <flux:button size="xs" type="button" wire:click="addTab">{{ __("Add tab") }}</flux:button>
                </div>
            </div>

            @error ("fields")
                <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
            @enderror

            @foreach ($tabs as $tabIndex => $tab)
                @php
                    $ungroupedFieldIndexes = collect($fields)->keys()->filter(fn($i) => ($fields[$i]["tab"] ?? "main") === $tab["handle"] && ($fields[$i]["section"] ?? "") === "")->values();
                @endphp

                <div class="space-y-3 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                    @if ($tabIndex !== 0 && $editingTabIndex === $tabIndex)
                        <div class="flex items-center gap-1">
                            <flux:input wire:model="tabs.{{ $tabIndex }}.handle" placeholder="handle" size="xs" class="w-28" />
                            <flux:input wire:model="tabs.{{ $tabIndex }}.display" placeholder="{{ __('Label') }}" size="xs" class="w-28" />
                            <flux:button size="xs" variant="primary" type="button" wire:click="$set('editingTabIndex', null)">{{ __("Done") }}</flux:button>
                        </div>
                        @error ("tabs.{$tabIndex}.handle")
                            <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                        @enderror
                    @else
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                <flux:text class="font-medium">{{ $tab["display"] ?: __("Main") }}</flux:text>
                                @if ($tabIndex !== 0)
                                    <flux:dropdown>
                                        <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil" wire:click="$set('editingTabIndex', {{ $tabIndex }})">{{ __("Edit") }}</flux:menu.item>
                                            <flux:menu.item icon="trash" variant="danger" wire:click="removeTab({{ $tabIndex }})" wire:confirm="{{ __('Delete this tab? Its fields will move back to Main.') }}">{{ __("Delete") }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                <flux:button size="xs" variant="ghost" type="button" wire:click="addSection({{ $tabIndex }})">{{ __("Add section") }}</flux:button>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3" wire:sort="reorderFields" wire:sort:group="blueprint-fields" wire:sort:group-id="{{ json_encode(['tab' => $tab['handle'], 'section' => '']) }}">
                        @forelse ($ungroupedFieldIndexes as $position => $index)
                            @include ("blueprints::livewire.partials.field-editor",
                                [
                                    "field" => $fields[$index],
                                    "path" => [$index],
                                    "prefix" => "fields.{$index}",
                                    "index" => $index,
                                    "isFirst" => $position === 0,
                                    "isLast" => $position === count($ungroupedFieldIndexes) - 1,
                                    "siblings" => $fields,
                                    "fieldtypes" => $fieldtypes,
                                    "conditionOperators" => $conditionOperators,
                                    "assetContainers" => $assetContainers,
                                    "linkableCollections" => $linkableCollections,
                                    "fieldsets" => $fieldsets,
                                    "taxonomies" => $taxonomies,
                                    "bardToolbarButtons" => $bardToolbarButtons,
                                    "tabs" => $tabs
                                ])
                        @empty
                            @if (empty($tab["sections"]))
                                <flux:text class="text-sm text-zinc-500">{{ __("No fields in this tab yet.") }}</flux:text>
                            @endif
                        @endforelse
                    </div>

                    <div class="space-y-3" wire:sort="reorderSections" wire:sort:group="blueprint-sections-{{ $tabIndex }}" wire:sort:group-id="{{ $tabIndex }}">
                        @foreach ($tab["sections"] ?? [] as $sectionIndex => $section)
                            @php $sectionFieldIndexes = collect($fields)->keys()->filter(fn($i) => ($fields[$i]["tab"] ?? "main") === $tab["handle"] && trim($section["handle"] ?? "") !== "" && ($fields[$i]["section"] ?? "") === $section["handle"])->values(); @endphp

                            <div wire:key="section-{{ $tabIndex }}-{{ $sectionIndex }}" wire:sort:item="{{ $sectionIndex }}" class="space-y-3 rounded-md border border-dashed border-zinc-300 p-3 dark:border-zinc-600">
                                @if ($editingSection === [$tabIndex, $sectionIndex])
                                    <div class="flex items-center gap-1">
                                        <flux:input wire:model="tabs.{{ $tabIndex }}.sections.{{ $sectionIndex }}.handle" placeholder="handle" size="sm" class="w-28" />
                                        <flux:input wire:model="tabs.{{ $tabIndex }}.sections.{{ $sectionIndex }}.display" placeholder="{{ __('Label') }}" size="sm" class="w-28" />
                                        <flux:button size="sm" variant="primary" type="button" wire:click="$set('editingSection', null)">{{ __("Done") }}</flux:button>
                                    </div>
                                    @error ("tabs.{$tabIndex}.sections.{$sectionIndex}.handle")
                                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                                    @enderror
                                @else
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1">
                                            <span wire:sort:handle class="flex h-full cursor-grab items-center px-1 text-zinc-400 hover:text-zinc-600 active:cursor-grabbing dark:hover:text-zinc-300" title="{{ __('Drag to reorder') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                                    <circle cx="5" cy="3" r="1.3" />
                                                    <circle cx="11" cy="3" r="1.3" />
                                                    <circle cx="5" cy="8" r="1.3" />
                                                    <circle cx="11" cy="8" r="1.3" />
                                                    <circle cx="5" cy="13" r="1.3" />
                                                    <circle cx="11" cy="13" r="1.3" />
                                                </svg>
                                            </span>
                                            <flux:text class="text-xs font-medium text-zinc-500">{{ $section["display"] ?: $section["handle"] }}</flux:text>
                                            <flux:dropdown>
                                                <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" />
                                                <flux:menu>
                                                    <flux:menu.item icon="pencil" wire:click="$set('editingSection', [{{ $tabIndex }}, {{ $sectionIndex }}])">{{ __("Edit") }}</flux:menu.item>
                                                    <flux:menu.item icon="trash" variant="danger" wire:click="removeSection({{ $tabIndex }}, {{ $sectionIndex }})" wire:confirm="{{ __('Delete this section? Its fields will be ungrouped.') }}">{{ __("Delete") }}</flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </div>
                                        <flux:button size="xs" variant="primary" type="button" wire:click="addField('{{ $tab['handle'] }}', '{{ $section['handle'] }}')">{{ __("Add field") }}</flux:button>
                                    </div>
                                @endif

                                <div wire:sort="reorderFields" wire:sort:group="blueprint-fields" wire:sort:group-id="{{ json_encode(['tab' => $tab['handle'], 'section' => $section['handle']]) }}" class="space-y-3">
                                    @forelse ($sectionFieldIndexes as $position => $index)
                                        @include ("blueprints::livewire.partials.field-editor",
                                            [
                                                "field" => $fields[$index],
                                                "path" => [$index],
                                                "prefix" => "fields.{$index}",
                                                "index" => $index,
                                                "isFirst" => $position === 0,
                                                "isLast" => $position === count($sectionFieldIndexes) - 1,
                                                "siblings" => $fields,
                                                "fieldtypes" => $fieldtypes,
                                                "conditionOperators" => $conditionOperators,
                                                "assetContainers" => $assetContainers,
                                                "linkableCollections" => $linkableCollections,
                                                "bardToolbarButtons" => $bardToolbarButtons,
                                                "tabs" => $tabs
                                            ])
                                    @empty
                                        <flux:text class="text-sm text-zinc-500">{{ __("No fields in this section yet.") }}</flux:text>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('blueprints.index')" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
        </div>
    </form>

    <flux:modal name="confirm-remove-field" class="min-w-[22rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this field?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" wire:click="removeField(deleteFieldPath)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
