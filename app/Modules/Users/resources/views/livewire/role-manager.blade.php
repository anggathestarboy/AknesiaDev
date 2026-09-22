<section class="w-full space-y-6" x-data="{ deleteRoleId: null }">
    <div class="flex items-center justify-between border py-2 px-4 rounded-xl dark:border-zinc-500">
        <div class="flex gap-2 items-center">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z" />
            </svg>

            <flux:heading size="xl">{{ __("Roles") }}</flux:heading>
        </div>

        <flux:button variant="primary" size="sm" :href="route('users.roles.create')" wire:navigate>{{ __("Add role") }}</flux:button>
    </div>

    <div class="bg-gray-200 dark:bg-zinc-900 p-1.5 rounded-xl">
        <div class="bg-white dark:bg-zinc-800 rounded-xl">
            <flux:table>
                <flux:table.columns class="bg-gray-200 dark:bg-zinc-900 [&>*]:text-white">
                    <flux:table.column>{{ __("Title") }}</flux:table.column>
                    <flux:table.column>{{ __("Handle") }}</flux:table.column>
                    <flux:table.column>{{ __("Permissions") }}</flux:table.column>
                    <flux:table.column>{{ __("Users") }}</flux:table.column>
                    <flux:table.column>{{ __("Groups") }}</flux:table.column>
                    <flux:table.column class="w-32"></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->roles as $role)
                        <flux:table.row wire:key="role-{{ $role->id }}">
                            <flux:table.cell>{{ $role->title }}</flux:table.cell>
                            <flux:table.cell>{{ $role->handle }}</flux:table.cell>
                            <flux:table.cell>
                                @if (in_array("super", $role->permissions, true))
                                    <flux:badge size="sm" color="red">{{ __("Super Admin") }}</flux:badge>
                                @else
                                    {{ count($role->permissions) }}
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                            <flux:table.cell>{{ $role->user_groups_count }}</flux:table.cell>
                            <flux:table.cell class="flex justify-end gap-2 w-32">
                                <flux:button size="sm" :href="route('users.roles.edit', ['role' => $role->handle])" wire:navigate>{{ __("Edit") }}</flux:button>
                                <flux:modal.trigger name="confirm-delete-role">
                                    <flux:button size="sm" variant="danger" type="button" x-on:click="deleteRoleId = {{ $role->id }}">{{ __("Delete") }}</flux:button>
                                </flux:modal.trigger>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">{{ __("No roles yet.") }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

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
