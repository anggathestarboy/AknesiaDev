<section class="w-full" x-data="{ deleteTermId: null }">
    <div class="flex items-center justify-between border py-2 px-4 rounded-xl dark:border-zinc-500">
        <div class="flex gap-2 items-center">
            <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.583 8.445h.01M10.86 19.71l-6.573-6.63a.993.993 0 0 1 0-1.4l7.329-7.394A.98.98 0 0 1 12.31 4l5.734.007A1.968 1.968 0 0 1 20 5.983v5.5a.992.992 0 0 1-.316.727l-7.44 7.5a.974.974 0 0 1-1.384.001Z" />
            </svg>
            <flux:heading size="xl">{{ __(":taxonomy", ["taxonomy" => $taxonomy->title]) }}</flux:heading>
        </div>

        @if ($this->availableBlueprints->isNotEmpty())
            <flux:button size="sm" variant="primary" :href="route('taxonomies.terms.create', ['taxonomy' => $taxonomy->handle, 'site' => $defaultSite, 'blueprint' => $taxonomy->effectiveBlueprint()?->handle])" wire:navigate>{{ __("New term") }}</flux:button>
        @else
            <flux:button variant="primary" size="sm" disabled>{{ __("New term") }}</flux:button>
        @endif
    </div>

    @if ($this->availableBlueprints->isEmpty())
        <flux:text class="text-zinc-500">{{ __("This taxonomy has no blueprint linked yet — link one in the Taxonomies module before creating terms.") }}</flux:text>
    @endif

    <div class="flex flex-wrap items-end gap-3 mt-3 mb-7">
        <div class="min-w-64 flex-1">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by slug or title…') }}" icon="magnifying-glass" size="sm" />
        </div>

        @if ($this->availableSites->count() > 1)
            <div class="w-48">
                <flux:select wire:model.live="siteFilter" size="sm">
                    <flux:select.option value="">{{ __("All sites") }}</flux:select.option>
                    @foreach ($this->availableSites as $availableSite)
                        <flux:select.option value="{{ $availableSite->handle }}">{{ $availableSite->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        @endif
    </div>

    <div class="bg-gray-200 dark:bg-zinc-900 p-1.5 rounded-xl">
        <div class="bg-white rounded-xl px-2">
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
                                <flux:modal.trigger name="confirm-delete-term">
                                    <flux:button size="sm" variant="danger" type="button" x-on:click="deleteTermId = {{ $term->id }}">{{ __("Delete") }}</flux:button>
                                </flux:modal.trigger>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="6">{{ $search !== "" || $siteFilter !== "" ? __("No terms match your filters.") : __("No terms yet.") }}</flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>
    </div>

    <flux:modal name="confirm-delete-term" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this term?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteTermId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
