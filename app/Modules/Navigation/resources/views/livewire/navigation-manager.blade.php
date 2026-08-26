<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Navigation') }}</flux:heading>
            <flux:subheading>{{ __('Menus shown around the site.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __('Add navigation') }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Handle') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->navigations as $navigation)
                <flux:table.row wire:key="navigation-{{ $navigation->id }}">
                    <flux:table.cell>{{ $navigation->title }}</flux:table.cell>
                    <flux:table.cell>{{ $navigation->handle }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-1">
                        <flux:button size="sm" variant="primary" :href="route('navigation.edit', $navigation->handle)" wire:navigate>{{ __('Edit tree') }}</flux:button>
                        <flux:button size="sm" wire:click="edit({{ $navigation->id }})">{{ __('Edit') }}</flux:button>
                        @if ($this->isProtected($navigation))
                            <flux:button size="sm" variant="danger" disabled tooltip="{{ __('This navigation is protected and cannot be deleted.') }}">{{ __('Delete') }}</flux:button>
                        @else
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $navigation->id }})" wire:confirm="{{ __('Delete this navigation?') }}">{{ __('Delete') }}</flux:button>
                        @endif
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                   <flux:table.cell colspan="3">
    {{ __('No navigations yet. Run') }}
    <code class="rounded bg-zinc-100 px-1.5 py-0.5 font-mono text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
        php artisan admin:sidebar
    </code>
    {{ __('to create the default admin navigation.') }}
</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="navigation-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit navigation') : __('Add navigation') }}</flux:heading>

            <div class="space-y-4">
                <flux:input wire:model.live="title" :label="__('Title')" placeholder="Main menu" required autofocus />
                <flux:input wire:model.live="handle" :label="__('Handle')" placeholder="main" required />
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
