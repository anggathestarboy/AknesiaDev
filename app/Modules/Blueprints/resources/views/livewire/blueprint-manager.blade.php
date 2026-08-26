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
                            @include('blueprints::livewire.partials.field-editor', [
                                'field' => $field,
                                'path' => [$index],
                                'prefix' => "fields.{$index}",
                                'index' => $index,
                                'isLast' => $index === count($fields) - 1,
                                'siblings' => $fields,
                                'fieldtypes' => $fieldtypes,
                                'conditionOperators' => $conditionOperators,
                                'assetContainers' => $assetContainers,
                                'linkableCollections' => $linkableCollections,
                            ])
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
