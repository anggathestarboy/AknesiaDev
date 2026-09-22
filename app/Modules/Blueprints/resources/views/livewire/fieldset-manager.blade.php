<section class="w-full space-y-6" x-data="{ deleteFieldsetId: null }">
    <div class="flex items-center justify-between border py-2 px-4 rounded-xl dark:border-zinc-500">
        <div class="flex gap-2 items-center">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 11.5h13m-13 0V18a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-6.5m-13 0V9a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v2.5M9 5h11a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-1" />
            </svg>

            <flux:heading size="xl">{{ __("Fieldsets") }}</flux:heading>
        </div>

        <flux:button variant="primary" size="sm" :href="route('blueprints.fieldsets.create')" wire:navigate>{{ __("New fieldset") }}</flux:button>
    </div>

    <div class="bg-gray-200 dark:bg-zinc-900 p-1.5 rounded-xl">
        <div class="bg-white rounded-xl dark:bg-zinc-800">
            <flux:table>
                <flux:table.columns class="bg-gray-200 dark:bg-zinc-900 [&>*]:text-white">
                    <flux:table.column>{{ __("Handle") }}</flux:table.column>
                    <flux:table.column>{{ __("Fields") }}</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->fieldsets as $fieldset)
                        <flux:table.row wire:key="fieldset-{{ $fieldset->id }}">
                            <flux:table.cell>{{ $fieldset->handle }}</flux:table.cell>
                            <flux:table.cell>{{ count($fieldset->data["fields"] ?? []) }}</flux:table.cell>
                            <flux:table.cell class="flex justify-end gap-2">
                                <flux:button size="sm" :href="route('blueprints.fieldsets.edit', $fieldset)" wire:navigate>{{ __("Edit") }}</flux:button>
                                <flux:modal.trigger name="confirm-delete-fieldset">
                                    <flux:button size="sm" variant="danger" type="button" x-on:click="deleteFieldsetId = {{ $fieldset->id }}">{{ __("Delete") }}</flux:button>
                                </flux:modal.trigger>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="3">{{ __("No fieldsets yet.") }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    <flux:modal name="confirm-delete-fieldset" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this fieldset?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteFieldsetId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
