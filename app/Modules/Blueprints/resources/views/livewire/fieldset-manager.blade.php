<section class="w-full space-y-6">
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
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->fieldsets as $fieldset)
                    <flux:table.row wire:key="fieldset-{{ $fieldset->id }}">
                        <flux:table.cell>{{ $fieldset->handle }}</flux:table.cell>
                        <flux:table.cell class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit({{ $fieldset->id }})">{{ __('Edit') }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $fieldset->id }})" wire:confirm="{{ __('Delete this fieldset?') }}">{{ __('Delete') }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="2">{{ __('No fieldsets yet.') }}</flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <flux:modal name="fieldset-form" class="max-w-lg" :show="$errors->isNotEmpty()">
            <form wire:submit="save" class="space-y-6">
                <flux:heading size="lg">{{ $editingId ? __('Edit fieldset') : __('New fieldset') }}</flux:heading>

                <flux:input wire:model="handle" :label="__('Handle')" required />
                <flux:textarea wire:model="data" :label="__('Fields (JSON)')" rows="8" />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </flux:modal>
    </section>
