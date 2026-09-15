<section class="w-full space-y-6" x-data="{ deleteUserId: null }">
    <div class="flex items-start justify-between">
        <div>
            <flux:heading size="lg">{{ __("Users") }}</flux:heading>
            <flux:subheading>{{ __("Control panel users.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('users.create')" wire:navigate>{{ __("Add user") }}</flux:button>
    </div>

    <div class="flex items-center justify-between gap-4">
        <div class="max-w-sm flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by name or email…') }}" icon="magnifying-glass" size="sm" />
        </div>

        @if (count($selected) > 0)
            <div class="flex items-end gap-2">
                <flux:text class="text-sm text-zinc-500">{{ __(':count selected', ['count' => count($selected)]) }}</flux:text>
                @if (auth()->user()->hasPermission("ban users"))
                    <flux:button size="sm" wire:click="bulkUnban">{{ __("Unban") }}</flux:button>
                    <flux:button size="sm" wire:click="bulkBan">{{ __("Ban") }}</flux:button>
                @endif
                @if (auth()->user()->hasPermission("delete users"))
                    <flux:modal.trigger name="confirm-bulk-delete-users">
                        <flux:button size="sm" variant="danger" type="button">{{ __("Delete") }}</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-8">
                <input type="checkbox" wire:click="toggleSelectAll" :checked="count($selected) > 0 && count($selected) === $this->users->count()" class="rounded border-zinc-300 dark:border-zinc-600" />
            </flux:table.column>
            <flux:table.column>{{ __("Name") }}</flux:table.column>
            <flux:table.column>{{ __("Email") }}</flux:table.column>
            <flux:table.column>{{ __("Status") }}</flux:table.column>
            <flux:table.column>{{ __("Roles") }}</flux:table.column>
            <flux:table.column>{{ __("Groups") }}</flux:table.column>
            <flux:table.column class="w-32"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->users as $user)
                <flux:table.row wire:key="user-{{ $user->id }}">
                    <flux:table.cell>
                        @if ($user->id === auth()->id())
                            <input type="checkbox" disabled title="{{ __("You can't select your own account.") }}" class="rounded border-zinc-300 dark:border-zinc-600" />
                        @else
                            <input type="checkbox" wire:model.live="selected" value="{{ $user->id }}" class="rounded border-zinc-300 dark:border-zinc-600" />
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        @if ($user->isBanned())
                            <flux:badge size="sm" color="red">{{ __("Banned") }}</flux:badge>
                        @else
                            <flux:badge size="sm" color="green">{{ __("Active") }}</flux:badge>
                        @endif
                    </flux:table.cell>
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
                        @if ($user->id !== auth()->id())
                            @if (auth()->user()->hasPermission("ban users"))
                                @if ($user->isBanned())
                                    <flux:button size="sm" wire:click="unban('{{ $user->id }}')">{{ __("Unban") }}</flux:button>
                                @else
                                    <flux:button size="sm" wire:click="ban('{{ $user->id }}')">{{ __("Ban") }}</flux:button>
                                @endif
                            @endif
                            <flux:modal.trigger name="confirm-delete-user">
                                <flux:button size="sm" variant="danger" type="button" x-on:click="deleteUserId = '{{ $user->id }}'">{{ __("Delete") }}</flux:button>
                            </flux:modal.trigger>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ $search !== "" ? __("No users match your search.") : __("No users yet.") }}</flux:table.cell>
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

    <flux:modal name="confirm-bulk-delete-users" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete :count user(s)?", ["count" => count($selected)]) }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone. Your own account, if selected, is skipped.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" wire:click="bulkDelete">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
