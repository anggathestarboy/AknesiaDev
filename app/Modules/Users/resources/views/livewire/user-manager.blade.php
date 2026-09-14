<section class="w-full space-y-6" x-data="{ deleteUserId: null }">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="lg">{{ __("Users") }}</flux:heading>
            <flux:subheading>{{ __("Control panel users.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('users.create')" wire:navigate>{{ __("Add user") }}</flux:button>
    </div>

    <div class="max-w-sm">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by name or email…') }}" icon="magnifying-glass" size="sm" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Name") }}</flux:table.column>
            <flux:table.column>{{ __("Email") }}</flux:table.column>
            <flux:table.column>{{ __("Roles") }}</flux:table.column>
            <flux:table.column>{{ __("Groups") }}</flux:table.column>
            <flux:table.column class="w-32"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row wire:key="user-{{ $user->id }}">
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($user->roles as $role)
                                <flux:badge size="sm">{{ $role->title }}</flux:badge>
                            @empty
                                <span class="text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @forelse ($user->userGroups as $group)
                                <flux:badge size="sm" color="zinc">{{ $group->title }}</flux:badge>
                            @empty
                                <span class="text-zinc-400">—</span>
                            @endforelse
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2 w-32">
                        <flux:button size="sm" :href="route('users.edit', ['user' => $user->id])" wire:navigate>{{ __("Edit") }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-user">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteUserId = '{{ $user->id }}'">{{ __("Delete") }}</flux:button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ $search !== "" ? __("No users match your search.") : __("No users yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="confirm-delete-user" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this user?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteUserId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
