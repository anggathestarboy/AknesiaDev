<section class="w-full max-w-6xl space-y-6">
    <div class="">
        <flux:link :href="route('collections.entries', $collection->handle)" wire:navigate class="text-sm">{{ __("← :collection entries", ["collection" => $collection->title]) }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $editingId ? __("Edit entry") : __("New entry") }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            @forelse ($this->blueprintFields() as $field)
                <div>
                    @if ($field["type"] === "toggle")
                        <flux:checkbox wire:model="data.{{ $field['handle'] }}" :label="$field['display']" :description="$field['instructions']" />
                    @elseif ($field["type"] === "select")
                        <flux:select wire:model="data.{{ $field['handle'] }}" :label="$field['display']" :description="$field['instructions']" :placeholder="$field['placeholder'] ?? __('Select an option')">
                            @foreach ($field["options"] ?? [] as $option)
                                <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    @elseif ($field["type"] === "date")
                        <flux:input type="date" wire:model="data.{{ $field['handle'] }}" :label="$field['display']" :description="$field['instructions']" />
                    @elseif ($field["type"] === "number")
                        <flux:input type="number" wire:model="data.{{ $field['handle'] }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" />
                    @elseif ($field["type"] === "textarea" || $field["type"] === "markdown")
                        <flux:textarea wire:model="data.{{ $field['handle'] }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" rows="6" />
                    @else
                        <flux:input wire:model="data.{{ $field['handle'] }}" :label="$field['display']" :placeholder="$field['placeholder']" :description="$field['instructions']" />
                    @endif
                </div>
            @empty
                <flux:text class="text-zinc-500">{{ __("This blueprint has no fields yet.") }}</flux:text>
            @endforelse
        </div>

        <div class="col-span-1 space-y-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            @if (count($this->siteLinks) > 1)
                <div>
                    <flux:text class="text-xs font-medium text-zinc-500">{{ __('Site') }}</flux:text>
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach ($this->siteLinks as $siteLink)
                            @if ($siteLink['active'])
                                <flux:button size="sm" variant="primary" disabled>{{ $siteLink['name'] }}</flux:button>
                            @else
                                <flux:button size="sm" variant="filled" :href="$siteLink['href']" wire:navigate>{{ $siteLink['name'] }}</flux:button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($this->availableBlueprints->count() > 1)
                <flux:select wire:model.live="blueprintHandle" :label="__('Blueprint')" size="sm">
                    @foreach ($this->availableBlueprints as $availableBlueprint)
                        <flux:select.option value="{{ $availableBlueprint->handle }}">{{ $availableBlueprint->handle }}</flux:select.option>
                    @endforeach
                </flux:select>
            @else
                <div>
                    <flux:text class="text-xs font-medium text-zinc-500">{{ __("Blueprint") }}</flux:text>
                    <flux:text>{{ $blueprintHandle }}</flux:text>
                </div>
            @endif

            <flux:input wire:model="title" :label="__('Title')" placeholder="{{ __('Entry title') }}" maxlength="300" required size="sm" />

            <flux:input wire:model="slug" :label="__('Slug')" placeholder="my-entry-slug" size="sm" />

            <flux:input type="number" wire:model="order" :label="__('Order')" placeholder="1" size="sm" />

            <flux:input type="datetime-local" wire:model="entryDate" :label="__('Date')" size="sm" />

            <flux:radio.group wire:model="status" :label="__('Status')">
                @foreach ($statuses as $value => $label)
                    <flux:radio value="{{ $value }}" label="{{ $label }}" />
                @endforeach
            </flux:radio.group>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button :href="route('collections.entries', $collection->handle)" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </div>
    </form>
</section>
