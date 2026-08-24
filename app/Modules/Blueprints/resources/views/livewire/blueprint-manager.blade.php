<section class="w-full space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">{{ __('Blueprints') }}</flux:heading>
                <flux:subheading>{{ __('Define field structures used by content.') }}</flux:subheading>
            </div>

            <flux:button variant="primary" wire:click="create">{{ __('New blueprint') }}</flux:button>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Namespace') }}</flux:table.column>
                <flux:table.column>{{ __('Handle') }}</flux:table.column>
                <flux:table.column>{{ __('Fields') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->blueprints as $blueprint)
                    <flux:table.row wire:key="blueprint-{{ $blueprint->id }}">
                        <flux:table.cell>{{ $blueprint->namespace ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $blueprint->handle }}</flux:table.cell>
                        <flux:table.cell>{{ count($blueprint->data['fields'] ?? []) }}</flux:table.cell>
                        <flux:table.cell class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit({{ $blueprint->id }})">{{ __('Edit') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $blueprint->id }})" wire:confirm="{{ __('Delete this blueprint?') }}">{{ __('Delete') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4">{{ __('No blueprints yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <flux:modal name="blueprint-form" class="max-w-3xl" :show="$errors->isNotEmpty()">
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">{{ $editingId ? __('Edit blueprint') : __('New blueprint') }}</flux:heading>

                <div class="grid grid-cols-2 gap-4">
                    @if ($this->availableNamespaces->isEmpty())
                        <div>
                            <flux:text class="text-sm font-medium">{{ __('Namespace') }}</flux:text>
                            <flux:text class="text-zinc-500">{{ __('No collections yet — create one in the Collections module first.') }}</flux:text>
                        </div>
                    @else
                        <flux:select wire:model="namespace" :label="__('Namespace')" placeholder="{{ __('No namespace') }}">
                            @foreach ($this->availableNamespaces as $availableNamespace)
                                <flux:select.option value="{{ $availableNamespace['value'] }}">{{ $availableNamespace['label'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @endif

                    <flux:input wire:model="handle" :label="__('Handle')" required />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:subheading>{{ __('Fields') }}</flux:subheading>
                        <flux:button size="sm" type="button" wire:click="addField">{{ __('Add field') }}</flux:button>
                    </div>

                    @error('fields')
                        <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                    @enderror

                    <div class="space-y-3">
                        @foreach ($fields as $index => $field)
                            <div wire:key="field-{{ $index }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="grid grid-cols-12 gap-3">
                                    <div class="col-span-3">
                                        <flux:input wire:model="fields.{{ $index }}.handle" :label="__('Handle')" placeholder="title" size="sm" />
                                    </div>
                                    <div class="col-span-3">
                                        <flux:input wire:model="fields.{{ $index }}.display" :label="__('Label')" placeholder="Title" size="sm" />
                                    </div>
                                    <div class="col-span-3">
                                        <flux:select wire:model.live="fields.{{ $index }}.type" :label="__('Type')" size="sm">
                                            @foreach ($fieldtypes as $value => $label)
                                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    </div>
                                    <div class="col-span-2 flex items-end pb-2">
                                        <flux:checkbox wire:model="fields.{{ $index }}.required" :label="__('Required')" />
                                    </div>
                                    <div class="col-span-1 flex items-end justify-end gap-1 pb-1">
                                        <flux:button size="sm" variant="ghost" icon="chevron-up" type="button" wire:click="moveFieldUp({{ $index }})" :disabled="$index === 0" />
                                        <flux:button size="sm" variant="ghost" icon="chevron-down" type="button" wire:click="moveFieldDown({{ $index }})" :disabled="$index === count($fields) - 1" />
                                        <flux:button size="sm" variant="ghost" icon="trash" type="button" wire:click="removeField({{ $index }})" />
                                    </div>
                                </div>

                                @if ($field['type'] === 'select')
                                    <div class="mt-3">
                                        <flux:input wire:model.live="fields.{{ $index }}.options" :label="__('Options (comma separated)')" placeholder="draft, published, archived" size="sm" />
                                    </div>
                                @endif

                                <div class="mt-3 grid grid-cols-4 gap-3">
                                    @if ($field['type'] === 'toggle' || $field['type'] === 'date')
                                        <flux:input :label="__('Placeholder')" placeholder="{{ __('Not applicable for this type') }}" size="sm" disabled />
                                    @else
                                        <flux:input wire:model="fields.{{ $index }}.placeholder" :label="__('Placeholder')" placeholder="{{ __('e.g. Enter a title') }}" size="sm" />
                                    @endif

                                    @if ($field['type'] === 'toggle')
                                        <flux:radio.group wire:model="fields.{{ $index }}.default" :label="__('Default value')">
                                            <flux:radio value="true" label="{{ __('True') }}" />
                                            <flux:radio value="false" label="{{ __('False') }}" />
                                        </flux:radio.group>
                                    @elseif ($field['type'] === 'select')
                                        <flux:select wire:model="fields.{{ $index }}.default" :label="__('Default value')" placeholder="{{ __('None') }}" size="sm">
                                            @foreach (collect(explode(',', $field['options'] ?? ''))->map(fn (string $option): string => trim($option))->filter() as $option)
                                                <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                                            @endforeach
                                        </flux:select>
                                    @elseif ($field['type'] === 'date')
                                        <flux:input type="date" wire:model="fields.{{ $index }}.default" :label="__('Default value')" size="sm" />
                                    @else
                                        <flux:input wire:model="fields.{{ $index }}.default" :label="__('Default value')" size="sm" />
                                    @endif

                                    <flux:input wire:model="fields.{{ $index }}.validation" :label="__('Validation rules')" placeholder="max:255|email" size="sm" />
                                    <flux:input wire:model="fields.{{ $index }}.instructions" :label="__('Instructions')" placeholder="{{ __('Help text shown to editors') }}" size="sm" />
                                </div>

                                <div class="mt-3">
                                    <flux:checkbox wire:model="fields.{{ $index }}.sometimes" :label="__('Sometimes')" :description="__('Only validate when this field is visible or submitted.')" />
                                </div>

                                <div class="mt-3 grid grid-cols-4 items-end gap-3 rounded-md bg-zinc-50 p-3 dark:bg-zinc-800/50">
                                    <flux:select wire:model.live="fields.{{ $index }}.condition_mode" :label="__('Condition')" size="sm">
                                        <flux:select.option value="always">{{ __('Always show') }}</flux:select.option>
                                        <flux:select.option value="show">{{ __('Show when') }}</flux:select.option>
                                        <flux:select.option value="hide">{{ __('Hide when') }}</flux:select.option>
                                    </flux:select>

                                    @if (($field['condition_mode'] ?? 'always') !== 'always')
                                        <flux:select wire:model="fields.{{ $index }}.condition_field" :label="__('Field')" placeholder="{{ __('Select a field') }}" size="sm">
                                            @foreach ($fields as $otherIndex => $otherField)
                                                @continue($otherIndex === $index || $otherField['handle'] === '')
                                                <flux:select.option value="{{ $otherField['handle'] }}">{{ $otherField['handle'] }}</flux:select.option>
                                            @endforeach
                                        </flux:select>

                                        <flux:select wire:model="fields.{{ $index }}.condition_operator" :label="__('Operator')" size="sm">
                                            @foreach ($conditionOperators as $value => $label)
                                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                            @endforeach
                                        </flux:select>

                                        <flux:input wire:model="fields.{{ $index }}.condition_value" :label="__('Value')" size="sm" />
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </section>
