<section class="w-full space-y-6" x-data="{ deleteFieldsetId: null }">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Fieldsets') }}</flux:heading>
            <flux:subheading>{{ __('Reusable field groups shared across blueprints.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('blueprints.fieldsets.create')" wire:navigate>{{ __('New fieldset') }}</flux:button>
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
                        <flux:button size="sm" :href="route('blueprints.fieldsets.edit', $fieldset)" wire:navigate>{{ __('Edit') }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-fieldset">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteFieldsetId = {{ $fieldset->id }}">{{ __('Delete') }}</flux:button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="3">{{ __('No fieldsets yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="confirm-delete-fieldset" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete this fieldset?') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('This cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteFieldsetId)">{{ __('Delete') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
