<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __("Taxonomies") }}</flux:heading>
            <flux:subheading>{{ __("Term groupings shared across collections (e.g. categories, tags).") }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __("New taxonomy") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Title") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("Blueprint") }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->taxonomies as $taxonomy)
                <flux:table.row wire:key="taxonomy-{{ $taxonomy->id }}">
                    <flux:table.cell>{{ $taxonomy->title }}</flux:table.cell>
                    <flux:table.cell>{{ $taxonomy->handle }}</flux:table.cell>
                    <flux:table.cell>{{ $taxonomy->effectiveBlueprint()?->handle ?? "—" }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" :href="route('taxonomies.terms', $taxonomy->handle)" wire:navigate>{{ __("Terms") }}</flux:button>
                        <flux:button size="sm" wire:click="edit({{ $taxonomy->id }})">{{ __("Settings") }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $taxonomy->id }})" wire:confirm="{{ __('Delete this taxonomy?') }}">{{ __("Delete") }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __("No taxonomies yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="taxonomy-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __("Edit taxonomy") : __("New taxonomy") }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" placeholder="News Categories" size="sm" required autofocus />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input wire:model="handle" :label="__('Handle')" size="sm" placeholder="news_categories" required />
                </div>
                <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateHandle" :tooltip="__('Regenerate from title')" />
            </div>

            @unless ($createBlueprint)
                <flux:select wire:model="blueprint" size="sm" :label="__('Blueprint')" placeholder="{{ __('No blueprint') }}">
                    @foreach ($this->availableBlueprints as $availableBlueprint)
                        <flux:select.option value="{{ $availableBlueprint->handle }}"> {{ $availableBlueprint->namespace ? "{$availableBlueprint->namespace}.{$availableBlueprint->handle}" : $availableBlueprint->handle }} </flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->availableBlueprints->isEmpty())
                    <flux:text class="text-zinc-500">{{ __("No blueprints yet — create one in the Blueprints module first.") }}</flux:text>
                @endif
            @endunless

            @if ($this->availableSites->isNotEmpty())
                <flux:checkbox.group wire:model="sites" :label="__('Languages')" :description="__('Which sites does this taxonomy support? The default site is the origin language and is always included.')">
                    @foreach ($this->availableSites as $availableSite)
                        <flux:checkbox value="{{ $availableSite->handle }}" label="{{ $availableSite->name }}" :description="$availableSite->handle === $this->defaultSiteHandle ? __('Default — origin language') : null" :disabled="$availableSite->handle === $this->defaultSiteHandle" />
                    @endforeach
                </flux:checkbox.group>
            @endif

            @unless ($editingId)
                <flux:checkbox wire:model.live="createBlueprint" :label="__('Create a blueprint for this taxonomy')" :description="__('Recommended — you\'ll add its fields right after saving. Turn off to pick an existing blueprint instead.')" />
            @endunless

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
