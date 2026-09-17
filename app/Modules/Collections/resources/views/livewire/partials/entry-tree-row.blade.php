@php
    $siteColors = ["indonesia" => "bg-red-500", "english" => "bg-blue-500"];
    $color = $siteColors[strtolower($entry->site)] ?? "bg-gray-400";
@endphp

<li wire:key="entry-{{ $entry->id }}" @if ($orderable) wire:sort:item="{{ $entry->id }}" @endif class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
    <div class="flex flex-wrap items-center gap-3 bg-white p-2 dark:bg-zinc-900">
        <div class="flex flex-col gap-2 items-center">
            <span class="size-2 shrink-0 rounded-full {{ $color }}"></span>
            @if ($orderable)
                <flux:icon.bars-3 wire:sort:handle class="size-4 shrink-0 cursor-grab text-zinc-400" />
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5 group w-max">
                @if ($entry->order === 1)
                    <svg class="w-3.5 h-3.5 text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5" />
                    </svg>
                @endif
                <a href="{{route('collections.entries.edit', ['collection' => $collection->handle, 'entry' => $entry->id])}}" class="font-medium text-md w-max group-hover:text-orange-700" wire:navigate>{{ $entry->data["title"] ?? "—" }}</a>
            </div>
            <div class="text-xs text-zinc-500">{{ $entry->slug ?? "—" }} · {{ $entry->blueprint ?? "—" }} · {{ $entry->updated_at?->diffForHumans() }}</div>
        </div>

        <div class="flex shrink-0 gap-2">
            <flux:badge size="sm" :color="match ($entry->status) {
        'published' => 'green',
        'scheduled' => 'blue',
        'need_review' => 'amber',
        default => 'zinc',
    }">{{ $statuses[$entry->status] ?? $entry->status }}</flux:badge>
            <flux:button size="sm" :href="route('collections.entries.edit', ['collection' => $collection->handle, 'entry' => $entry->id])" wire:navigate>{{ __("Edit") }}</flux:button>
            <flux:modal.trigger name="confirm-delete-entry">
                <flux:button size="sm" variant="danger" type="button" x-on:click="deleteEntryId = '{{ $entry->id }}'; deleteSiblingCount = {{ $this->localeSiblingCount($entry) }}; deleteAllLocales = true">{{ __("Delete") }}</flux:button>
            </flux:modal.trigger>
        </div>
    </div>

    @if ($entry->children->isNotEmpty())
        <ul @if ($orderable) wire:sort="reorderChildren" wire:sort:group-id="{{ $entry->id }}" @endif class="space-y-1.5 border-t border-zinc-200 bg-gray-100 p-1.5 dark:border-zinc-700 dark:bg-zinc-950">
            @foreach ($entry->children as $child)
                @include ("collections::livewire.partials.entry-tree-row", ["entry" => $child, "collection" => $collection, "statuses" => $statuses, "orderable" => $orderable])
            @endforeach
        </ul>
    @endif
</li>
