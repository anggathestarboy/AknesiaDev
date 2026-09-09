<section class="mx-auto w-full max-w-5xl space-y-6" x-data x-on:keydown.window.ctrl.s.prevent="$wire.save()">
    <div class="flex items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ $navigation->title }}</flux:heading>
            <flux:subheading>{{ __('Fields come from this navigation\'s blueprint — add a replicator field there for repeatable links.') }}</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="save">{{ __('Save') }}</flux:button>
    </div>

    <form wire:submit="save" class="space-y-6 rounded-lg border-2 border-zinc-300 p-6 dark:border-zinc-500">
        @if (count($this->blueprintTabs) > 1)
            <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
                @foreach ($this->blueprintTabs as $tab)
                    <button type="button" wire:click="setActiveTab('{{ $tab['handle'] }}')" class="border-b-2 px-3 py-2 text-sm {{ $activeTab === $tab['handle'] ? 'border-zinc-800 font-medium text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">{{ $tab['display'] }}</button>
                @endforeach
            </div>
        @endif

        @php
            $activeTabDef = collect($this->blueprintTabs)->firstWhere('handle', $activeTab);
            $tabSections = $activeTabDef['sections'] ?? [];
            $tabFields = collect($this->blueprintFields())->filter(fn ($field) => ($field['tab'] ?? 'main') === $activeTab);
            $ungroupedFields = $tabFields->filter(fn ($field) => ($field['section'] ?? '') === '');
        @endphp

        @foreach ($ungroupedFields as $field)
            <div>
                @if ($field['type'] === 'replicator')
                    @include('navigation::livewire.partials.menu-list', [
                        'field' => $field,
                        'path' => [$field['handle']],
                        'rows' => $data[$field['handle']] ?? [],
                    ])
                @else
                    @include('collections::livewire.partials.entry-field', [
                        'field' => $field,
                        'path' => [$field['handle']],
                        'assetIds' => $field['type'] === 'assets' ? $data[$field['handle']] ?? [] : null,
                        'linkValue' => $field['type'] === 'link' ? $data[$field['handle']] ?? '' : null,
                        'bardValue' => $field['type'] === 'bard' ? $data[$field['handle']] ?? '' : null,
                    ])
                @endif
            </div>
        @endforeach

        @foreach ($tabSections as $section)
            @php $sectionFields = $tabFields->filter(fn ($field) => ($field['section'] ?? '') === $section['handle']); @endphp
            @if ($sectionFields->isNotEmpty())
                <div class="space-y-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:subheading>{{ $section['display'] }}</flux:subheading>

                    @foreach ($sectionFields as $field)
                        <div>
                            @if ($field['type'] === 'replicator')
                                @include('navigation::livewire.partials.menu-list', [
                                    'field' => $field,
                                    'path' => [$field['handle']],
                                    'rows' => $data[$field['handle']] ?? [],
                                ])
                            @else
                                @include('collections::livewire.partials.entry-field', [
                                    'field' => $field,
                                    'path' => [$field['handle']],
                                    'assetIds' => $field['type'] === 'assets' ? $data[$field['handle']] ?? [] : null,
                                    'linkValue' => $field['type'] === 'link' ? $data[$field['handle']] ?? '' : null,
                                    'bardValue' => $field['type'] === 'bard' ? $data[$field['handle']] ?? '' : null,
                                ])
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach

        <div class="flex justify-end gap-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:button :href="route('navigation.index')" wire:navigate variant="filled">{{ __('Cancel') }}</flux:button>
        </div>
    </form>

    @livewire('media::asset-picker')
</section>
