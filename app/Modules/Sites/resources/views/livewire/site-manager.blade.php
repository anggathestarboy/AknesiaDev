<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __("Sites") }}</flux:heading>
            <flux:subheading>{{ __("Multi-site configuration.") }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __("Add site") }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __("Name") }}</flux:table.column>
            <flux:table.column>{{ __("Handle") }}</flux:table.column>
            <flux:table.column>{{ __("URL") }}</flux:table.column>
            <flux:table.column>{{ __("Locale") }}</flux:table.column>
            <flux:table.column>{{ __("Language") }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->sites as $index => $site)
                <flux:table.row wire:key="site-{{ $site->id }}">
                    <flux:table.cell>{{ $site->name }}</flux:table.cell>
                    <flux:table.cell>{{ $site->handle }}</flux:table.cell>
                    <flux:table.cell>{{ $site->url }}</flux:table.cell>
                    <flux:table.cell>{{ $site->locale }}</flux:table.cell>
                    <flux:table.cell>{{ $site->lang }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-1">
                        <flux:button size="sm" variant="ghost" icon="chevron-up" wire:click="moveUp({{ $site->id }})" :disabled="$index === 0" />
                        <flux:button size="sm" variant="ghost" icon="chevron-down" wire:click="moveDown({{ $site->id }})" :disabled="$index === $this->sites->count() - 1" />
                        <flux:button size="sm" wire:click="edit({{ $site->id }})">{{ __("Edit") }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $site->id }})" wire:confirm="{{ __('Delete this site?') }}">{{ __("Delete") }}</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6">{{ __("No sites yet.") }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="site-form" class="max-w-2xl" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __("Edit site") : __("Add site") }}</flux:heading>

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="name" :label="__('Name')" placeholder="English" required autofocus />
                <flux:input wire:model="handle" :label="__('Handle')" placeholder="english" required />
                <flux:input wire:model="url" :label="__('URL')" placeholder="https://example.com/" required />
                <flux:input wire:model="locale" :label="__('Locale')" placeholder="en_US" required />
                <flux:input wire:model="lang" :label="__('Language')" placeholder="en" required />
            </div>

            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <flux:subheading>{{ __("Additional attributes") }}</flux:subheading>
                    <flux:button size="sm" type="button" wire:click="addAttribute">{{ __("Add attribute") }}</flux:button>
                </div>

                <div class="space-y-2">
                    @foreach ($customAttributes as $index => $attribute)
                        <div wire:key="attribute-{{ $index }}" class="grid grid-cols-12 gap-3">
                            <div class="col-span-5">
                                <flux:input wire:model="customAttributes.{{ $index }}.key" placeholder="native_name" size="sm" />
                            </div>
                            <div class="col-span-6">
                                <flux:input wire:model="customAttributes.{{ $index }}.value" placeholder="Deutsch" size="sm" />
                            </div>
                            <div class="col-span-1 flex items-start">
                                <flux:button size="sm" variant="ghost" icon="trash" type="button" wire:click="removeAttribute({{ $index }})" />
                            </div>
                        </div>
                    @endforeach

                    @if (empty($customAttributes))
                        <flux:text class="text-zinc-500">{{ __("No custom attributes yet.") }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
