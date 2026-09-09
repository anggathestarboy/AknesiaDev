<section class="w-full space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="lg">{{ __("User Groups") }}</flux:heading>
            <flux:subheading>{{ __("Group users together and grant them roles all at once.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __("Add group") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Title") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("Roles") }}</flux:table.column>
            <flux:table.column>{{ __("Users") }}</flux:table.column>
            <flux:table.column class="w-32"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->groups as $group)
                <flux:table.row wire:key="group-{{ $group->id }}">
                    <flux:table.cell>{{ $group->title }}</flux:table.cell>
                    <flux:table.cell>{{ $group->handle }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($group->roles as $role)
                                <flux:badge size="sm">{{ $role->title }}</flux:badge>
                            @empty
                                <span class="text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $group->users_count }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2 w-32">
                        <flux:button size="sm" wire:click="edit({{ $group->id }})">{{ __("Edit") }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $group->id }})" wire:confirm="{{ __('Delete this group?') }}">{{ __("Delete") }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __("No user groups yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="user-group-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __("Edit group") : __("Add group") }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" placeholder="Marketing" required autofocus />

            <div class="flex items-end gap-2">
                <flux:input wire:model="handle" :label="__('Handle')" class="flex-1" required />
                <flux:button size="sm" type="button" icon="arrow-path" class="mt-7" wire:click="regenerateHandle" :tooltip="__('Regenerate from title')" />
            </div>

            <flux:field>
                <flux:label>{{ __("Roles") }}</flux:label>
                <flux:checkbox.group wire:model="roleHandles">
                    @forelse ($this->availableRoles as $role)
                        <flux:checkbox value="{{ $role->handle }}" label="{{ $role->title }}" />
                    @empty
                        <flux:text class="text-zinc-500">{{ __("No roles yet.") }}</flux:text>
                    @endforelse
                </flux:checkbox.group>
            </flux:field>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
