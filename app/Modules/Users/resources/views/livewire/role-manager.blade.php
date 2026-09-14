<section class="w-full space-y-6" x-data="{ deleteRoleId: null }">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="lg">{{ __("Roles") }}</flux:heading>
            <flux:subheading>{{ __("Define what each role is allowed to do.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('users.roles.create')" wire:navigate>{{ __("Add role") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Title") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("Permissions") }}</flux:table.column>
            <flux:table.column>{{ __("Users") }}</flux:table.column>
            <flux:table.column class="w-32"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->roles as $role)
                <flux:table.row wire:key="role-{{ $role->id }}">
                    <flux:table.cell>{{ $role->title }}</flux:table.cell>
                    <flux:table.cell>{{ $role->handle }}</flux:table.cell>
                    <flux:table.cell>
                        @if (in_array('super', $role->permissions, true))
                            <flux:badge size="sm" color="red">{{ __("Super Admin") }}</flux:badge>
                        @else
                            {{ count($role->permissions) }}
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2 w-32">
                        <flux:button size="sm" :href="route('users.roles.edit', ['role' => $role->handle])" wire:navigate>{{ __("Edit") }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-role">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteRoleId = {{ $role->id }}">{{ __("Delete") }}</flux:button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __("No roles yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="confirm-delete-role" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this role?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteRoleId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
