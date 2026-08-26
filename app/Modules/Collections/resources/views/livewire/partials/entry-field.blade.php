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
@else
    <flux:input wire:model="{{ $prefix }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" />
@endif
