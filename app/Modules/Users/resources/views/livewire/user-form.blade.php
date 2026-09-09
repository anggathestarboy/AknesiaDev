<section class="w-full max-w-3xl space-y-6">
    <div>
        <flux:link :href="route('users.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Users") }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $editingId ? __("Edit user") : __("Add user") }}</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4">
            <flux:input wire:model="name" :label="__('Name')" placeholder="Jane Doe" required autofocus />
            <flux:input wire:model="email" type="email" :label="__('Email')" placeholder="jane@example.com" required />
        </div>

        <flux:input wire:model="password" type="password" :label="__('Password')" :description="$editingId ? __('Leave blank to keep the current password.') : null" placeholder="••••••••" :required="!$editingId" />

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

        <flux:field>
            <flux:label>{{ __("User groups") }}</flux:label>
            <flux:checkbox.group wire:model="groupHandles">
                @forelse ($this->availableGroups as $group)
                    <flux:checkbox value="{{ $group->handle }}" label="{{ $group->title }}" />
                @empty
                    <flux:text class="text-zinc-500">{{ __("No user groups yet.") }}</flux:text>
                @endforelse
            </flux:checkbox.group>
        </flux:field>

        <div class="flex justify-end gap-2">
            <flux:button :href="route('users.index')" variant="filled" wire:navigate>{{ __("Cancel") }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
        </div>
    </form>
</section>
