<section class="w-full space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <flux:link :href="route('collections.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Collections") }}</flux:link>
            <flux:heading size="lg" class="mt-5">{{ __(":collection entries", ["collection" => $collection->title]) }}</flux:heading>
            <flux:subheading>{{ __("Handle: :handle", ["handle" => $collection->handle]) }}</flux:subheading>
        </div>

        @if ($this->availableBlueprints->isNotEmpty())
            <form method="GET" action="{{ route('collections.entries.create', ['collection' => $collection->handle, 'site' => $defaultSite]) }}" class="flex flex-col items-end gap-2">
                <flux:button variant="primary" type="submit">{{ __("New entry") }}</flux:button>

                <flux:select name="blueprint" size="sm">
                    @foreach ($this->availableBlueprints as $availableBlueprint)
                        <option value="{{ $availableBlueprint->handle }}" @selected ($availableBlueprint->handle === $collection->effectiveBlueprint()?->handle)>{{ $availableBlueprint->handle }}</option>
                    @endforeach
                </flux:select>
            </form>
        @else
            <flux:button variant="primary" disabled>{{ __("New entry") }}</flux:button>
        @endif
    </div>

    @if ($this->availableBlueprints->isEmpty())
        <flux:text class="text-zinc-500">{{ __("This collection has no blueprint linked yet — link one in the Collections module before creating entries.") }}</flux:text>
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

    @if ($orderable)
        <flux:text class="text-sm text-zinc-500">{{ __("Drag the handle to reorder. The top entry becomes the site's homepage.") }}</flux:text>
    @endif

    @if ($collection->structured())
        <ul @if ($orderable) wire:sort="reorderRoot" @endif class="space-y-2">
            @forelse ($this->entryTree as $entry)
                @include("collections::livewire.partials.entry-tree-row", ["entry" => $entry, "collection" => $collection, "statuses" => $statuses, "orderable" => $orderable])
            @empty
                <li class="text-sm text-zinc-500">{{ $search !== "" || $siteFilter !== "" ? __("No entries match your filters.") : __("No entries yet.") }}</li>
            @endforelse
        </ul>
    @else
        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-8"></flux:table.column>
                <flux:table.column class="w-25">{{ __("Site") }}</flux:table.column>
                <flux:table.column>{{ __("Title") }}</flux:table.column>
                <flux:table.column>{{ __("Slug") }}</flux:table.column>
                <flux:table.column>{{ __("Blueprint") }}</flux:table.column>
                <flux:table.column>{{ __("Updated") }}</flux:table.column>
                <flux:table.column class="w-28">{{ __("Status") }}</flux:table.column>
                <flux:table.column class="w-32"></flux:table.column>
            </flux:table.columns>

            <flux:table.rows :wire:sort="$orderable ? 'reorderRoot' : null">
                @forelse ($this->entries as $entry)
                    <flux:table.row wire:key="entry-{{ $entry->id }}" :wire:sort:item="$orderable ? $entry->id : null">
                        <flux:table.cell>
                            @if ($orderable)
                                <flux:icon.bars-3 wire:sort:handle class="size-4 shrink-0 cursor-grab text-zinc-400" />
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            @php
                                $siteColors = [
                                    "indonesia" => "bg-red-500",
                                    "english" => "bg-blue-500",
                                ];

                                $color = $siteColors[strtolower($entry->site)] ?? "bg-gray-400";
                            @endphp

                            <div class="flex items-center gap-2">
                                <span class="size-2 rounded-full {{ $color }}"></span>
                                <span>{{ $entry->site }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2" style="padding-inline-start: {{ ($entry->depth ?? 0) * 0.2 }}rem">
                                @if (($entry->depth ?? 0) > 0)
                                    <flux:icon.arrow-turn-down-right class="size-3.5 shrink-0 text-zinc-400" />
                                @endif

                                <span>{{ $entry->data["title"] ?? "—" }}</span>

                                @if ($entry->order === 1)
                                    <flux:badge size="sm">
                                        <svg class="w-5 h-5 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5" />
                                        </svg>
                                    </flux:badge>
                                @endif

                                @if ($entry->parentTitle ?? null)
                                    <flux:badge size="sm" color="zinc">{{ __("Parent: :title", ["title" => $entry->parentTitle]) }}</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $entry->slug ?? "—" }}</flux:table.cell>
                        <flux:table.cell>{{ $entry->blueprint ?? "—" }}</flux:table.cell>
                        <flux:table.cell>{{ $entry->updated_at?->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="w-28">
                            <flux:badge :color="match ($entry->status) {
        'published' => 'green',
        'need_review' => 'amber',
        default => 'zinc',
    }"> {{ $statuses[$entry->status] ?? $entry->status }} </flux:badge>
                        </flux:table.cell>
                        <flux:table.cell class="flex justify-end gap-2 w-32">
                            <flux:button size="sm" :href="route('collections.entries.edit', ['collection' => $collection->handle, 'entry' => $entry->id])" wire:navigate>{{ __("Edit") }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete('{{ $entry->id }}')" wire:confirm="{{ __('Delete this entry?') }}">{{ __("Delete") }}</flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7"> {{ $search !== "" || $siteFilter !== "" ? __("No entries match your filters.") : __("No entries yet.") }} </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    @endif
</section>
