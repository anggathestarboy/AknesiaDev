<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __("Blueprints") }}</flux:heading>
            <flux:subheading>{{ __("Define field structures used by content.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __("New blueprint") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Namespace") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("Fields") }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->blueprints as $blueprint)
                <flux:table.row wire:key="blueprint-{{ $blueprint->id }}">
                    <flux:table.cell>{{ $blueprint->namespace ?? "—" }}</flux:table.cell>
                    <flux:table.cell>{{ $blueprint->handle }}</flux:table.cell>
                    <flux:table.cell>{{ count($blueprint->data["fields"] ?? []) }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" wire:click="edit({{ $blueprint->id }})">{{ __("Edit") }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $blueprint->id }})" wire:confirm="{{ __('Delete this blueprint?') }}">{{ __("Delete") }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __("No blueprints yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="blueprint-form" class="min-w-[85%]" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __("Edit blueprint") : __("New blueprint") }}</flux:heading>

            <div class="grid grid-cols-2 gap-4">
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
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:subheading>{{ __("Fields") }}</flux:subheading>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="ghost" type="button" wire:click="$dispatch('collapse-all-fields')">{{ __("Collapse all") }}</flux:button>
                        <flux:button size="sm" variant="ghost" type="button" wire:click="$dispatch('expand-all-fields')">{{ __("Expand all") }}</flux:button>
                        <flux:button size="sm" type="button" wire:click="addTab">{{ __("Add tab") }}</flux:button>
                    </div>
                </div>

                @error ("fields")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                @foreach ($tabs as $tabIndex => $tab)
                    @php
                        $ungroupedFieldIndexes = collect($fields)->keys()->filter(fn($i) => ($fields[$i]["tab"] ?? "main") === $tab["handle"] && ($fields[$i]["section"] ?? "") === "")->values();
                    @endphp

                    <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @if ($tabIndex !== 0 && $editingTabIndex === $tabIndex)
                            <div class="flex items-center gap-1">
                                <flux:input wire:model="tabs.{{ $tabIndex }}.handle" placeholder="handle" size="sm" class="w-28" />
                                <flux:input wire:model="tabs.{{ $tabIndex }}.display" placeholder="{{ __('Label') }}" size="sm" class="w-28" />
                                <flux:button size="sm" variant="primary" type="button" wire:click="$set('editingTabIndex', null)">{{ __("Done") }}</flux:button>
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
                                    <flux:button size="sm" variant="ghost" type="button" wire:click="addSection({{ $tabIndex }})">{{ __("Add section") }}</flux:button>
                                    <flux:button size="sm" type="button" wire:click="addField('{{ $tab['handle'] }}')">{{ __("Add field") }}</flux:button>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3">
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
                                        "tabs" => $tabs
                                    ])
                            @empty
                                @if (empty($tab["sections"]))
                                    <flux:text class="text-sm text-zinc-500">{{ __("No fields in this tab yet.") }}</flux:text>
                                @endif
                            @endforelse
                        </div>

                        @foreach ($tab["sections"] ?? [] as $sectionIndex => $section)
                            @php $sectionFieldIndexes = collect($fields)->keys()->filter(fn($i) => ($fields[$i]["tab"] ?? "main") === $tab["handle"] && trim($section["handle"] ?? "") !== "" && ($fields[$i]["section"] ?? "") === $section["handle"])->values(); @endphp

                            <div wire:key="section-{{ $tabIndex }}-{{ $sectionIndex }}" class="space-y-3 rounded-md border border-dashed border-zinc-300 p-3 dark:border-zinc-600">
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
                                            <flux:text class="text-xs font-medium text-zinc-500">{{ $section["display"] ?: $section["handle"] }}</flux:text>
                                            <flux:dropdown>
                                                <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" />
                                                <flux:menu>
                                                    <flux:menu.item icon="pencil" wire:click="$set('editingSection', [{{ $tabIndex }}, {{ $sectionIndex }}])">{{ __("Edit") }}</flux:menu.item>
                                                    <flux:menu.item icon="trash" variant="danger" wire:click="removeSection({{ $tabIndex }}, {{ $sectionIndex }})" wire:confirm="{{ __('Delete this section? Its fields will be ungrouped.') }}">{{ __("Delete") }}</flux:menu.item>
                                                </flux:menu>
                                            </flux:dropdown>
                                        </div>
                                        <flux:button size="sm" variant="ghost" type="button" wire:click="addField('{{ $tab['handle'] }}', '{{ $section['handle'] }}')">{{ __("Add field") }}</flux:button>
                                    </div>
                                @endif

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
                                            "tabs" => $tabs
                                        ])
                                @empty
                                    <flux:text class="text-sm text-zinc-500">{{ __("No fields in this section yet.") }}</flux:text>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
