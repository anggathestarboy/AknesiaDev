<section class="w-full space-y-6" x-data="{ deleteFieldPath: null }">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Fieldsets') }}</flux:heading>
            <flux:subheading>{{ __('Reusable field groups shared across blueprints.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __('New fieldset') }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Handle') }}</flux:table.column>
            <flux:table.column>{{ __('Fields') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->fieldsets as $fieldset)
                <flux:table.row wire:key="fieldset-{{ $fieldset->id }}">
                    <flux:table.cell>{{ $fieldset->handle }}</flux:table.cell>
                    <flux:table.cell>{{ count($fieldset->data['fields'] ?? []) }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" wire:click="edit({{ $fieldset->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $fieldset->id }})" wire:confirm="{{ __('Delete this fieldset?') }}">{{ __('Delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">{{ __('No fieldsets yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="fieldset-form" class="min-w-[85%]" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit fieldset') : __('New fieldset') }}</flux:heading>

            <flux:input wire:model="handle" :label="__('Handle')" description:trailing="{{ __('Lowercase letters, numbers and underscores only — no spaces.') }}" required />

            <div class="space-y-3">
                <div class="flex items-center justify-end gap-2">
                    <flux:button size="xs" variant="ghost" type="button" wire:click="$dispatch('collapse-all-fields')">{{ __('Collapse all') }}</flux:button>
                    <flux:button size="xs" variant="ghost" type="button" wire:click="$dispatch('expand-all-fields')">{{ __('Expand all') }}</flux:button>
                    <flux:button size="xs" type="button" wire:click="addField">{{ __('Add field') }}</flux:button>
                </div>

                @error ('fields')
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror

                <div class="space-y-3" wire:sort="reorderFields" wire:sort:group="fieldset-fields" wire:sort:group-id="{{ json_encode(['tab' => 'main', 'section' => '']) }}">
                    @forelse ($fields as $index => $field)
                        @include ('blueprints::livewire.partials.field-editor',
                            [
                                'field' => $field,
                                'path' => [$index],
                                'prefix' => "fields.{$index}",
                                'index' => $index,
                                'isFirst' => $index === 0,
                                'isLast' => $index === count($fields) - 1,
                                'siblings' => $fields,
                                'fieldtypes' => $fieldtypes,
                                'conditionOperators' => $conditionOperators,
                                'assetContainers' => $assetContainers,
                                'linkableCollections' => $linkableCollections,
                                'fieldsets' => $fieldsets,
                                'taxonomies' => $taxonomies,
                                'bardToolbarButtons' => $bardToolbarButtons,
                                'tabs' => $tabs
                            ])
                    @empty
                        <flux:text class="text-sm text-zinc-500">{{ __('No fields yet.') }}</flux:text>
                    @endforelse
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

    <flux:modal name="confirm-remove-field" class="min-w-[22rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete this field?') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('This cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" wire:click="removeField(deleteFieldPath)">{{ __('Delete') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
