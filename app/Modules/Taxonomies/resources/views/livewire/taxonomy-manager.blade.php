<section class="w-full space-y-6" x-data="{ deleteTaxonomyId: null }">
    <div class="flex items-center justify-between border py-2 px-4 rounded-xl dark:border-zinc-500">
        <div class="flex gap-2 items-center">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m17 21-5-4-5 4V3.889a.92.92 0 0 1 .244-.629.808.808 0 0 1 .59-.26h8.333a.81.81 0 0 1 .589.26.92.92 0 0 1 .244.63V21Z" />
            </svg>

            <flux:heading size="xl">{{ __("Taxonomies") }}</flux:heading>
        </div>

        <flux:button variant="primary" size="sm" wire:click="create">{{ __("New taxonomy") }}</flux:button>
    </div>

    <div class="bg-gray-200 dark:bg-zinc-900 p-1.5 rounded-xl">
        <div class="bg-white rounded-xl dark:bg-zinc-800">
            <flux:table>
                <flux:table.columns class="bg-gray-200 dark:bg-zinc-900 [&>*]:text-white">
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
                                <flux:modal.trigger name="confirm-delete-taxonomy">
                                    <flux:button size="sm" variant="danger" type="button" x-on:click="deleteTaxonomyId = {{ $taxonomy->id }}">{{ __("Delete") }}</flux:button>
                                </flux:modal.trigger>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4">{{ __("No taxonomies yet.") }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

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

            <flux:radio.group wire:model="saveAction" :label="__('Action after save')" :description="__('Where the term form goes after saving one of this taxonomy\'s terms.')">
                <flux:radio value="listing" :label="__('Go to listing')" />
                <flux:radio value="editing" :label="__('Continue editing')" />
                <flux:radio value="create_another" :label="__('Create another')" />
            </flux:radio.group>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete-taxonomy" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this taxonomy?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteTaxonomyId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
