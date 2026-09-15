<section class="w-full space-y-6" x-data="{ deleteCollectionId: null }">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __("Collections") }}</flux:heading>
            <flux:subheading>{{ __("Content types (e.g. blog, pages).") }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __("New collection") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Title") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("Blueprint") }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->collections as $collection)
                <flux:table.row wire:key="collection-{{ $collection->id }}">
                    <flux:table.cell>{{ $collection->title }}</flux:table.cell>
                    <flux:table.cell>{{ $collection->handle }}</flux:table.cell>
                    <flux:table.cell>{{ $collection->effectiveBlueprint()?->handle ?? "—" }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" :href="route('collections.entries', $collection->handle)" wire:navigate>{{ __("Entries") }}</flux:button>
                        <flux:button size="sm" wire:click="edit({{ $collection->id }})">{{ __("Settings") }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-collection">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteCollectionId = {{ $collection->id }}">{{ __("Delete") }}</flux:button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __("No collections yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="collection-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __("Edit collection") : __("New collection") }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" required autofocus />
            <flux:input wire:model="handle" :label="__('Handle')" required />

            @if ($this->availableSites->isNotEmpty())
                <flux:checkbox.group wire:model.live="sites" :label="__('Sites')" :description="__('Which sites is this collection available on? Leave all checked for no restriction. The default site is the origin language and can\'t be removed.')">
                    @foreach ($this->availableSites as $availableSite)
                        <flux:checkbox value="{{ $availableSite->handle }}" label="{{ $availableSite->name }}" :description="$availableSite->handle === $this->defaultSiteHandle ? __('Default — origin language') : null" :disabled="$availableSite->handle === $this->defaultSiteHandle" />
                    @endforeach
                </flux:checkbox.group>
            @endif

            @if (count($sites) > 1)
                <flux:radio.group wire:model.live="routeMode" variant="segmented" :label="__('Route')">
                    <flux:radio value="single" :label="__('Single')" />
                    <flux:radio value="per_site" :label="__('Per-site')" />
                </flux:radio.group>
            @endif

            @php $routeVariables = __("Available variables: {slug}, {year}, {month}, {day}") . ($structured ? ", {parent_uri}" : ""); @endphp

            @if ($routeMode === "per_site" && count($sites) > 1)
                <div class="space-y-4">
                    @foreach ($this->availableSites->whereIn("handle", $sites) as $routeSite)
                        <flux:input wire:model="routes.{{ $routeSite->handle }}" :label="__(':site route', ['site' => $routeSite->name])" placeholder="blog/{slug}" :description="$routeVariables" />
                    @endforeach
                </div>
            @else
                <flux:input wire:model="route" :label="count($sites) > 1 ? null : __('Route')" placeholder="{{ $structured ? '{parent_uri}/{slug}' : 'blog/{slug}' }}" :description="$routeVariables" />
            @endif

            <div class="space-y-4 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:checkbox wire:model.live="structured" :label="__('Structured')" :description="__('Let entries be nested under a parent entry, enabling the {parent_uri} route variable.')" />

                <flux:checkbox wire:model="orderable" :label="__('Orderable')" :description="__('Let entries be manually reordered by drag-and-drop.')" />

                <flux:checkbox wire:model="livePreview" :label="__('Live Preview')" :description="__('Let editors preview an entry on the live site as they edit it, even before it\'s published.')" />

                <flux:checkbox wire:model="schemaEnabled" :label="__('Schema')" :description="__('Add a Schema tab to this collection\'s entries for attaching schema.org JSON-LD blocks (Article, Organization, etc.) to individual entries.')" />

                <flux:checkbox wire:model="seoEnabled" :label="__('SEO')" :description="__('Add an SEO tab to this collection\'s entries for meta title/description, robots, sitemap hints, etc.')" />

                @if ($structured)
                    <flux:input type="number" min="1" wire:model="maxDepth" :label="__('Max depth')" placeholder="{{ __('No limit') }}" />

                    <flux:checkbox wire:model="expectsRoot" :label="__('Expect a root page')" :description="__('Require exactly one top-level entry (e.g. a homepage) instead of allowing several.')" />
                @endif
            </div>

            <flux:radio.group wire:model="saveAction" :label="__('Action after save')" :description="__('Where the entry form goes after saving one of this collection\'s entries.')">
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

    <flux:modal name="confirm-delete-collection" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this collection?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("Collections with existing entries can't be deleted — delete those first. This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteCollectionId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
