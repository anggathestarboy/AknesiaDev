<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Redirect Manager') }}</flux:heading>
            <flux:subheading>{{ __('Redirects applied to incoming requests.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __('New redirect') }}</flux:button>
    </div>

    <div class="max-w-sm">
        <flux:input wire:model.live.debounce.300ms="search" :label="__('Search')" placeholder="{{ __('Search by source or target…') }}" icon="magnifying-glass" size="sm" />
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Source') }}</flux:table.column>
            <flux:table.column>{{ __('Target') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Match type') }}</flux:table.column>
            <flux:table.column>{{ __('Active') }}</flux:table.column>
            <flux:table.column>{{ __('Hits') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->redirects as $redirect)
                <flux:table.row wire:key="redirect-{{ $redirect->id }}">
                    <flux:table.cell>{{ $redirect->source }}</flux:table.cell>
                    <flux:table.cell>{{ $redirect->target }}</flux:table.cell>
                    <flux:table.cell>{{ $redirect->status_code }}</flux:table.cell>
                    <flux:table.cell>{{ $redirect->match_type }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" :color="$redirect->is_active ? 'green' : 'zinc'">{{ $redirect->is_active ? __('Active') : __('Inactive') }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $redirect->hits }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-1">
                        <flux:button size="sm" wire:click="view({{ $redirect->id }})">{{ __('View') }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $redirect->id }})" wire:confirm="{{ __('Delete this redirect?') }}">{{ __('Delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="7">{{ $search !== '' ? __('No redirects match your search.') : __('No redirects yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="redirect-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Redirect details') : __('New redirect') }}</flux:heading>

            <div class="space-y-4">
                <flux:input wire:model="source" :label="__('Source')" placeholder="/old-path" required />
                <flux:input wire:model="target" :label="__('Target')" placeholder="/new-path" required />

                <div class="grid grid-cols-2 gap-4">
                    <flux:select wire:model="status_code" :label="__('Status code')">
                        <flux:select.option value="301">301 — {{ __('Permanent') }}</flux:select.option>
                        <flux:select.option value="302">302 — {{ __('Temporary') }}</flux:select.option>
                        <flux:select.option value="307">307 — {{ __('Temporary (keep method)') }}</flux:select.option>
                        <flux:select.option value="308">308 — {{ __('Permanent (keep method)') }}</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="match_type" :label="__('Match type')">
                        <flux:select.option value="exact">{{ __('Exact') }}</flux:select.option>
                        <flux:select.option value="prefix">{{ __('Prefix') }}</flux:select.option>
                        <flux:select.option value="regex">{{ __('Regex') }}</flux:select.option>
                    </flux:select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:input type="datetime-local" wire:model="start_at" :label="__('Start at')" />
                    <flux:input type="datetime-local" wire:model="end_at" :label="__('End at')" />
                </div>

                <flux:switch wire:model="is_active" :label="__('Active')" />
                <flux:switch wire:model="is_external" :label="__('External target')" />
                <flux:switch wire:model="keep_path" :label="__('Keep path')" />

                <flux:text class="text-sm text-zinc-500">{{ __(':count hits so far.', ['count' => $hits]) }}</flux:text>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
