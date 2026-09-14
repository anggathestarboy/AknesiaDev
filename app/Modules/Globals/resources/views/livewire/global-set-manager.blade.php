<section class="w-full space-y-6" x-data="{ deleteGlobalSetId: null }">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __("Globals") }}</flux:heading>
            <flux:subheading>{{ __("Site-wide values, e.g. footer text or contact info.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __("New global set") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Title") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("Blueprint") }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->globalSets as $globalSet)
                <flux:table.row wire:key="global-set-{{ $globalSet->id }}">
                    <flux:table.cell>{{ $globalSet->title }}</flux:table.cell>
                    <flux:table.cell>{{ $globalSet->handle }}</flux:table.cell>
                    <flux:table.cell>{{ $globalSet->effectiveBlueprint()?->handle ?? "—" }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" :href="route('globals.edit', $globalSet->handle)" wire:navigate>{{ __("Edit values") }}</flux:button>
                        <flux:button size="sm" wire:click="edit({{ $globalSet->id }})">{{ __("Settings") }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-global-set">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteGlobalSetId = {{ $globalSet->id }}">{{ __("Delete") }}</flux:button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __("No global sets yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="global-set-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __("Edit global set") : __("New global set") }}</flux:heading>

            <flux:input wire:model="title" size="sm" :label="__('Title')" required autofocus />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input wire:model="handle" size="sm" :label="__('Handle')" class="flex-1" required />
                </div>
                <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateHandle" :tooltip="__('Regenerate from title')" />
            </div>

            @unless ($createBlueprint)
                <flux:select size="sm" wire:model="blueprint" :label="__('Blueprint')" placeholder="{{ __('No blueprint') }}">
                    @foreach ($this->availableBlueprints as $availableBlueprint)
                        <flux:select.option value="{{ $availableBlueprint->handle }}"> {{ $availableBlueprint->namespace ? "{$availableBlueprint->namespace}.{$availableBlueprint->handle}" : $availableBlueprint->handle }} </flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->availableBlueprints->isEmpty())
                    <flux:text class="text-zinc-500">{{ __("No blueprints yet — create one in the Blueprints module first.") }}</flux:text>
                @endif
            @endunless

            @unless ($editingId)
                <flux:checkbox wire:model.live="createBlueprint" :label="__('Create a blueprint for this global set')" :description="__('Recommended — you\'ll add its fields right after saving. Turn off to pick an existing blueprint instead.')" />
            @endunless

            @if ($this->availableSites->isNotEmpty())
                <flux:checkbox.group wire:model="sites" :label="__('Languages')" :description="__('Which sites does this global set have values for? The default site is the origin language and is always included.')">
                    @foreach ($this->availableSites as $availableSite)
                        <flux:checkbox value="{{ $availableSite->handle }}" label="{{ $availableSite->name }}" :description="$availableSite->handle === $this->defaultSiteHandle ? __('Default — origin language') : null" :disabled="$availableSite->handle === $this->defaultSiteHandle" />
                    @endforeach
                </flux:checkbox.group>
            @endif

            <flux:radio.group wire:model="saveAction" :label="__('Action after save')" :description="__('Where the values form goes after saving this global set\'s values.')">
                <flux:radio value="listing" :label="__('Go to listing')" />
                <flux:radio value="editing" :label="__('Continue editing')" />
            </flux:radio.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete-global-set" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this global set?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("Its values will be deleted too. This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteGlobalSetId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
