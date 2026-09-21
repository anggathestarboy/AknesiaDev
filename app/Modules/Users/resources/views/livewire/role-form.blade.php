<section class="w-full max-w-4xl space-y-6">
    <div>
        <flux:link :href="route('users.roles.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Roles") }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $editingId ? __("Edit role") : __("Add role") }}</flux:heading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="grid gap-4">
            <flux:input size="sm" wire:model="title" :label="__('Title')" placeholder="Editor" required autofocus />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input size="sm" wire:model="handle" :label="__('Handle')" class="flex-1 min-w-full" required />
                </div>
                <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateHandle" :tooltip="__('Regenerate from title')" />
            </div>
        </div>

        <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/30">
            <flux:switch wire:model.live="superAdmin" :label="__('Super Admin')" :description="__('Full access, bypasses every permission check below. When on, this role can do everything, including things granted to no other role.')" />
        </div>

        @unless ($superAdmin)
            <div class="space-y-4">
                <flux:heading size="md">{{ __("Permissions") }}</flux:heading>

                @foreach ($this->permissionGroups as $group)
                    <div class="rounded-lg border border-zinc-200 p-4 dark:border-white/10">
                        <div class="mb-2 flex items-center justify-between">
                            <flux:subheading>{{ $group["group"] }}</flux:subheading>

                            @php $groupValues = collect(app(\App\Modules\Users\Services\PermissionRegistry::class)->flatten($group["permissions"]))->pluck("value")->all(); @endphp
                            <div class="flex gap-2">
                                <flux:button size="xs" type="button" wire:click="checkAll({{ \Illuminate\Support\Js::from($groupValues) }})">{{ __("Check all") }}</flux:button>
                                <flux:button size="xs" type="button" variant="subtle" wire:click="uncheckAll({{ \Illuminate\Support\Js::from($groupValues) }})">{{ __("Uncheck all") }}</flux:button>
                            </div>
                        </div>

                        <flux:checkbox.group wire:model="permissions">
                            @foreach ($group["permissions"] as $permission)
                                <flux:checkbox value="{{ $permission['value'] }}" label="{{ $permission['label'] }}" :description="$permission['description']" />

                                @if ($permission["children"] !== [])
                                    <div class="ms-6 space-y-3 dark:border-white/10 mb-10 last:mb-0">
                                        @foreach ($permission["children"] as $child)
                                            <flux:checkbox value="{{ $child['value'] }}" label="{{ $child['label'] }}" :description="$child['description']" />
                                        @endforeach
                                    </div>
                                @endif
                            @endforeach
                        </flux:checkbox.group>
                    </div>
                @endforeach
            </div>
        @endunless

        <div class="flex justify-end gap-2">
            <flux:button :href="route('users.roles.index')" variant="filled" wire:navigate>{{ __("Cancel") }}</flux:button>
            <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
        </div>
    </form>
</section>
