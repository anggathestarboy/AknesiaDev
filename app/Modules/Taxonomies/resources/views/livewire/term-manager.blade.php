<section class="w-full space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <flux:link :href="route('taxonomies.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Taxonomies") }}</flux:link>
            <flux:heading size="lg" class="mt-5">{{ __(":taxonomy terms", ["taxonomy" => $taxonomy->title]) }}</flux:heading>
            <flux:subheading>{{ __("Handle: :handle", ["handle" => $taxonomy->handle]) }}</flux:subheading>
        </div>

        @if ($this->availableBlueprints->isNotEmpty())
            <flux:button variant="primary" :href="route('taxonomies.terms.create', ['taxonomy' => $taxonomy->handle, 'site' => $defaultSite, 'blueprint' => $taxonomy->effectiveBlueprint()?->handle])" wire:navigate>{{ __("New term") }}</flux:button>
        @else
            <flux:button variant="primary" disabled>{{ __("New term") }}</flux:button>
        @endif
    </div>

    @if ($this->availableBlueprints->isEmpty())
        <flux:text class="text-zinc-500">{{ __("This taxonomy has no blueprint linked yet — link one in the Taxonomies module before creating terms.") }}</flux:text>
    @endif

    <div class="flex flex-wrap items-end gap-3">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by slug or title…') }}" icon="magnifying-glass" size="sm" />
        </div>

        @if ($this->availableSites->count() > 1)
            <div class="w-48">
                <flux:select wire:model.live="siteFilter" :label="__('Site')" size="sm">
                    <flux:select.option value="">{{ __("All sites") }}</flux:select.option>
                    @foreach ($this->availableSites as $availableSite)
                        <flux:select.option value="{{ $availableSite->handle }}">{{ $availableSite->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column class="w-25">{{ __("Site") }}</flux:table.column>
            <flux:table.column>{{ __("Title") }}</flux:table.column>
            <flux:table.column>{{ __("Slug") }}</flux:table.column>
            <flux:table.column>{{ __("Blueprint") }}</flux:table.column>
            <flux:table.column>{{ __("Updated") }}</flux:table.column>
            <flux:table.column class="w-32"></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->terms as $term)
                <flux:table.row wire:key="term-{{ $term->id }}">
                    <flux:table.cell>
                        @php
                            $siteColors = [
                                "indonesia" => "bg-red-500",
                                "english" => "bg-blue-500",
                            ];

                            $color = $siteColors[strtolower($term->site)] ?? "bg-gray-400";
                        @endphp

                        <div class="flex items-center gap-2">
                            <span class="size-2 rounded-full {{ $color }}"></span>
                            <span>{{ $term->site }}</span>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $term->data["title"] ?? "—" }}</flux:table.cell>
                    <flux:table.cell>{{ $term->slug ?? "—" }}</flux:table.cell>
                    <flux:table.cell>{{ $term->blueprint ?? "—" }}</flux:table.cell>
                    <flux:table.cell>{{ $term->updated_at?->diffForHumans() }}</flux:table.cell>
                    <flux:table.cell class="flex w-32 justify-end gap-2">
                        <flux:button size="sm" :href="route('taxonomies.terms.edit', ['taxonomy' => $taxonomy->handle, 'term' => $term->id])" wire:navigate>{{ __("Edit") }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $term->id }})" wire:confirm="{{ __('Delete this term?') }}">{{ __("Delete") }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ $search !== "" || $siteFilter !== "" ? __("No terms match your filters.") : __("No terms yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
