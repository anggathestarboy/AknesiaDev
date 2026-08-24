<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Collections') }}</flux:heading>
            <flux:subheading>{{ __('Content types (e.g. blog, pages).') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __('New collection') }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Handle') }}</flux:table.column>
            <flux:table.column>{{ __('Blueprint') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->collections as $collection)
                <flux:table.row wire:key="collection-{{ $collection->id }}">
                    <flux:table.cell>{{ $collection->title }}</flux:table.cell>
                    <flux:table.cell>{{ $collection->handle }}</flux:table.cell>
                    <flux:table.cell>{{ $collection->effectiveBlueprint()?->handle ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" :href="route('collections.entries', $collection->handle)" wire:navigate>{{ __('Entries') }}</flux:button>
                        <flux:button size="sm" wire:click="edit({{ $collection->id }})">{{ __('Edit') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $collection->id }})" wire:confirm="{{ __('Delete this collection?') }}">{{ __('Delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('No collections yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="collection-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit collection') : __('New collection') }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" required autofocus />
            <flux:input wire:model="handle" :label="__('Handle')" required />

            <flux:select wire:model="blueprint" :label="__('Blueprint')" placeholder="{{ __('No blueprint') }}">
                @foreach ($this->availableBlueprints as $availableBlueprint)
                    <flux:select.option value="{{ $availableBlueprint->handle }}">
                        {{ $availableBlueprint->namespace ? "{$availableBlueprint->namespace}.{$availableBlueprint->handle}" : $availableBlueprint->handle }}
                    </flux:select.option>
                @endforeach
            </flux:select>

            @if ($this->availableBlueprints->isEmpty())
                <flux:text class="text-zinc-500">{{ __('No blueprints yet — create one in the Blueprints module first.') }}</flux:text>
            @endif

            <flux:textarea wire:model="settings" :label="__('Other settings (JSON)')" rows="6" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
