@php
    $prefix = 'data.'.implode('.', $path);
@endphp

@if ($field['type'] === 'toggle')
    <flux:checkbox wire:model="{{ $prefix }}" :label="$field['display']" :description="$field['instructions']" />
@elseif ($field['type'] === 'select')
    <flux:select wire:model="{{ $prefix }}" :label="$field['display']" :description="$field['instructions']" :placeholder="$field['placeholder'] ?? __('Select an option')">
        @foreach ($field['options'] ?? [] as $option)
            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
        @endforeach
    </flux:select>
@elseif ($field['type'] === 'date')
    <flux:input type="date" wire:model="{{ $prefix }}" :label="$field['display']" :description="$field['instructions']" />
@elseif ($field['type'] === 'number')
    <flux:input type="number" wire:model="{{ $prefix }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" />
@elseif ($field['type'] === 'textarea' || $field['type'] === 'markdown')
    <flux:textarea wire:model="{{ $prefix }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" rows="4" />
@elseif ($field['type'] === 'replicator')
    <div>
        <flux:text class="text-sm font-medium">{{ $field['display'] }}</flux:text>
        @if ($field['instructions'])
            <flux:text class="block text-xs text-zinc-500">{{ $field['instructions'] }}</flux:text>
        @endif

        <div class="mt-2 space-y-3 border-l-2 border-zinc-200 pl-3 dark:border-zinc-700">
            @forelse ($rows ?? [] as $rowIndex => $row)
                <div wire:key="replicator-{{ implode('-', $path) }}-{{ $rowIndex }}" class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    <div class="grid grid-cols-2 gap-3">
                        @foreach ($field['replicator_fields'] ?? [] as $sub)
                            <div>
                                @include('collections::livewire.partials.entry-field', [
                                    'field' => $sub,
                                    'path' => [...$path, $rowIndex, $sub['handle']],
                                    'rows' => $sub['type'] === 'replicator' ? ($row[$sub['handle']] ?? []) : null,
                                    'assetIds' => $sub['type'] === 'assets' ? ($row[$sub['handle']] ?? []) : null,
                                ])
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-end">
                        <flux:button size="sm" variant="ghost" icon="trash" type="button" wire:click='removeReplicatorRow({{ json_encode($path) }}, {{ $rowIndex }})'>{{ __('Remove') }}</flux:button>
                    </div>
                </div>
            @empty
                <flux:text class="text-sm text-zinc-500">{{ __('No entries yet.') }}</flux:text>
            @endforelse

            <flux:button size="sm" variant="filled" type="button" wire:click='addReplicatorRow({{ json_encode($path) }})'>{{ __('Add :label', ['label' => $field['display']]) }}</flux:button>
        </div>
    </div>
@elseif ($field['type'] === 'assets')
    @php
        $selectedCount = count($assetIds ?? []);
        $maxReached = ($field['max_items'] ?? null) !== null && $selectedCount >= $field['max_items'];
    @endphp
    <div>
        <flux:text class="text-sm font-medium">{{ $field['display'] }}</flux:text>
        @if ($field['instructions'])
            <flux:text class="block text-xs text-zinc-500">{{ $field['instructions'] }}</flux:text>
        @endif
        @if (($field['min_items'] ?? null) !== null || ($field['max_items'] ?? null) !== null)
            <flux:text class="block text-xs text-zinc-500">
                @if (($field['min_items'] ?? null) !== null && ($field['max_items'] ?? null) !== null)
                    {{ __(':min–:max items', ['min' => $field['min_items'], 'max' => $field['max_items']]) }}
                @elseif (($field['min_items'] ?? null) !== null)
                    {{ __('At least :min items', ['min' => $field['min_items']]) }}
                @else
                    {{ __('Up to :max items', ['max' => $field['max_items']]) }}
                @endif
            </flux:text>
        @endif

        <div class="mt-2 flex flex-wrap gap-2">
            @foreach ($this->selectedAssets($assetIds ?? []) as $asset)
                <div wire:key="selected-{{ implode('-', $path) }}-{{ $asset->id }}" class="group relative size-16 overflow-hidden rounded border border-zinc-200 dark:border-zinc-700">
                    @if (str_starts_with((string) $asset->mime_type, 'image/'))
                        <img src="{{ $asset->url }}" class="h-full w-full object-cover" alt="{{ $asset->title }}">
                    @else
                        <div class="flex h-full w-full items-center justify-center bg-zinc-100 text-[10px] uppercase dark:bg-zinc-800">{{ $asset->extension }}</div>
                    @endif
                    <button type="button" wire:click='removeAsset({{ json_encode($path) }}, {{ $asset->id }})' class="absolute right-0 top-0 rounded-bl bg-black/60 px-1 text-xs text-white opacity-0 group-hover:opacity-100">✕</button>
                </div>
            @endforeach

            @unless ($maxReached)
                <button
                    type="button"
                    wire:click='$dispatch("open-asset-picker", { path: {{ json_encode($path) }}, container: {{ json_encode($field['options'] ?? '') }}, selected: {{ json_encode($assetIds ?? []) }} })'
                    class="flex size-16 items-center justify-center rounded border border-dashed border-zinc-300 text-zinc-400 hover:border-zinc-400 hover:text-zinc-600 dark:border-zinc-600"
                >
                    <flux:icon.plus class="size-5" />
                </button>
            @endunless
        </div>
    </div>
@else
    <flux:input wire:model="{{ $prefix }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" />
@endif
