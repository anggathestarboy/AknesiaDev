<section class="w-full space-y-6">
    <div>
        <flux:link :href="route('forms.index')" variant="subtle" wire:navigate class="text-sm">{{ __('← Forms') }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ __(':title submissions', ['title' => $form->title]) }}</flux:heading>
    </div>

    <flux:table>
        <flux:table.columns>
            @foreach ($this->fields as $field)
                <flux:table.column>{{ $field['display'] ?: $field['handle'] }}</flux:table.column>
            @endforeach
            <flux:table.column>{{ __('Submitted') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->submissions as $submission)
                <flux:table.row wire:key="submission-{{ $submission->id }}">
                    @foreach ($this->fields as $field)
                        @php $value = $submission->data[$field['handle']] ?? null; @endphp
                        <flux:table.cell>{{ is_array($value) ? implode(', ', $value) : $value }}</flux:table.cell>
                    @endforeach
                    <flux:table.cell>{{ $submission->created_at?->format('Y-m-d H:i') }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end">
                        <flux:button size="sm" variant="danger" wire:click="delete('{{ $submission->id }}')" wire:confirm="{{ __('Delete this submission?') }}">{{ __('Delete') }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="{{ count($this->fields) + 2 }}">{{ __('No submissions yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
