<section class="w-full space-y-6">
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
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $globalSet->id }})" wire:confirm="{{ __('Delete this global set and its values?') }}">{{ __("Delete") }}</flux:button>
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

            <flux:input wire:model="title" :label="__('Title')" required autofocus />

            <div class="flex items-start gap-2">
                <flux:input wire:model="handle" :label="__('Handle')" description:trailing="{{ __('Lowercase letters, numbers and underscores only — no spaces.') }}" class="flex-1" required />
                <flux:button size="sm" type="button" icon="arrow-path" class="mt-7" wire:click="regenerateHandle" :tooltip="__('Regenerate from title')" />
            </div>

            @unless ($editingId)
                <flux:checkbox wire:model.live="createBlueprint" :label="__('Create a blueprint for this global set')" :description="__('Recommended — you\'ll add its fields right after saving. Turn off to pick an existing blueprint instead.')" />
            @endunless

            @if ($this->availableSites->isNotEmpty())
                <flux:checkbox.group wire:model="sites" :label="__('Languages')" :description="__('Which sites does this global set have values for? The default site is the origin language and is always included.')">
                    @foreach ($this->availableSites as $availableSite)
                        <flux:checkbox
                            value="{{ $availableSite->handle }}"
                            label="{{ $availableSite->name }}"
                            :description="$availableSite->handle === $this->defaultSiteHandle ? __('Default — origin language') : null"
                            :disabled="$availableSite->handle === $this->defaultSiteHandle"
                        />
                    @endforeach
                </flux:checkbox.group>
            @endif

            @unless ($createBlueprint)
                <flux:select wire:model="blueprint" :label="__('Blueprint')" placeholder="{{ __('No blueprint') }}">
                    @foreach ($this->availableBlueprints as $availableBlueprint)
                        <flux:select.option value="{{ $availableBlueprint->handle }}"> {{ $availableBlueprint->namespace ? "{$availableBlueprint->namespace}.{$availableBlueprint->handle}" : $availableBlueprint->handle }} </flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->availableBlueprints->isEmpty())
                    <flux:text class="text-zinc-500">{{ __("No blueprints yet — create one in the Blueprints module first.") }}</flux:text>
                @endif
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
