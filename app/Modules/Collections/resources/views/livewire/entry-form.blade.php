<section
    class="w-full max-w-6xl space-y-6"
    x-on:keydown.window.ctrl.s.prevent="$wire.save()"
    x-on:keydown.window.meta.s.prevent="$wire.save()"
>
    <div class="">
        <flux:link :href="route('collections.entries', $collection->handle)" wire:navigate class="text-sm">{{ __("← :collection entries", ["collection" => $collection->title]) }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $editingId ? __("Edit entry") : __("New entry") }}</flux:heading>
    </div>

    @if ($errors->any())
        <flux:callout variant="danger" icon="exclamation-triangle" :heading="__('Please fix the following before saving:')">
            <ul class="list-disc space-y-1 pl-4 text-sm">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </flux:callout>
    @endif

    <form wire:submit="save" class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            @if (count($this->blueprintTabs) > 1)
                <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
                    @foreach ($this->blueprintTabs as $tab)
                        <button type="button" wire:click="setActiveTab('{{ $tab['handle'] }}')" class="border-b-2 px-3 py-2 text-sm {{ $activeTab === $tab['handle'] ? 'border-zinc-800 font-medium text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer' }}">{{ $tab["display"] }}</button>

                    @endforeach
                </div>
            @endif

            @php
                $activeTabDef = collect($this->blueprintTabs)->firstWhere("handle", $activeTab);
                $tabSections = $activeTabDef["sections"] ?? [];
                $tabFields = collect($this->blueprintFields())->filter(fn($field) => ($field["tab"] ?? "main") === $activeTab);
                $ungroupedFields = $tabFields->filter(fn($field) => ($field["section"] ?? "") === "");
            @endphp

            @forelse ($ungroupedFields as $field)
                <div>
                    @if (!($field["localizable"] ?? false) && !$this->isOriginLocale())
                        <flux:field>
                            <flux:label>{{ $field["display"] }}</flux:label>
                            <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">{{ is_scalar($data[$field["handle"]] ?? null) ? ($data[$field["handle"]] ?: "—") : __("Set in the default language") }}</div>
                            <flux:description>{{ __("Same for every language — edit it from the default language.") }}</flux:description>
                        </flux:field>
                    @else
                        @include ("collections::livewire.partials.entry-field",
                            [
                                "field" => $field,
                                "path" => [$field["handle"]],
                                "rows" => $field["type"] === "replicator" ? $data[$field["handle"]] ?? [] : null,
                                "assetIds" => $field["type"] === "assets" ? $data[$field["handle"]] ?? [] : null,
                                "linkValue" => $field["type"] === "link" ? $data[$field["handle"]] ?? "" : null,
                                "taxonomyIds" => $field["type"] === "taxonomy" ? $data[$field["handle"]] ?? [] : null
                            ])
                    @endif
                </div>
            @empty
                @if ($tabFields->isEmpty())
                    <flux:text class="text-zinc-500"> {{ count($this->blueprintFields()) === 0 ? __("This blueprint has no fields yet.") : __("No fields in this tab.") }} </flux:text>
                @endif
            @endforelse

            @foreach ($tabSections as $section)
                @php $sectionFields = $tabFields->filter(fn($field) => ($field["section"] ?? "") === $section["handle"]); @endphp
                @if ($sectionFields->isNotEmpty())
                    <div class="space-y-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                        <flux:subheading>{{ $section["display"] }}</flux:subheading>

                        @foreach ($sectionFields as $field)
                            <div>
                                @if (!($field["localizable"] ?? false) && !$this->isOriginLocale())
                                    <flux:field>
                                        <flux:label>{{ $field["display"] }}</flux:label>
                                        <div class="rounded-md border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">{{ is_scalar($data[$field["handle"]] ?? null) ? ($data[$field["handle"]] ?: "—") : __("Set in the default language") }}</div>
                                        <flux:description>{{ __("Same for every language — edit it from the default language.") }}</flux:description>
                                    </flux:field>
                                @else
                                    @include ("collections::livewire.partials.entry-field",
                                        [
                                            "field" => $field,
                                            "path" => [$field["handle"]],
                                            "rows" => $field["type"] === "replicator" ? $data[$field["handle"]] ?? [] : null,
                                            "assetIds" => $field["type"] === "assets" ? $data[$field["handle"]] ?? [] : null,
                                            "linkValue" => $field["type"] === "link" ? $data[$field["handle"]] ?? "" : null,
                                            "taxonomyIds" => $field["type"] === "taxonomy" ? $data[$field["handle"]] ?? [] : null
                                        ])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>

        <div class="col-span-1 space-y-5 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="">
                <div class="flex justify-between gap-2 pb-2">
                    <flux:button variant="primary" class="w-full" type="submit">{{ __("Save") }}</flux:button>
                    <flux:button :href="route('collections.entries', $collection->handle)" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
                </div>
                @if ($this->resolvedUrl)
                    <flux:button size="sm" icon="arrow-top-right-on-square" href="{{ $this->resolvedUrl }}" target="_blank" class="w-full">{{ __("Visit") }}</flux:button>
                @endif
            </div>

            @if (count($this->siteLinks) > 1)
                <div class="border border-gray-300 p-2 rounded-xl">
                    {{-- <flux:text class="text-xs font-medium text-zinc-500">{{ __("Site") }}</flux:text> --}}
                    <div class="mt-1 flex flex-col gap-1">
                        @foreach ($this->siteLinks as $siteLink)
                            @if ($siteLink["active"])
                                <flux:button size="sm" variant="primary" disabled>{{ $siteLink["name"] }}</flux:button>
                            @else
                                <flux:button size="sm" variant="filled" :href="$siteLink['href']" wire:navigate>{{ $siteLink["name"] }}</flux:button>
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

            @if ($collection->structured())
                <flux:select wire:model="parentId" :label="__('Parent')" size="sm">
                    <flux:select.option value="">{{ __("None — top level") }}</flux:select.option>
                    @foreach ($this->availableParentEntries as $parentEntry)
                        <flux:select.option value="{{ $parentEntry->id }}">{{ $parentEntry->data["title"] ?? ($parentEntry->slug ?? $parentEntry->id) }}</flux:select.option>
                    @endforeach
                </flux:select>
                @error ("parentId")
                    <flux:text class="text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                @enderror
            @endif

            <flux:input wire:model="title" :label="__('Title')" placeholder="{{ __('Entry title') }}" maxlength="300" required size="sm" />

            <flux:input wire:model="slug" :label="__('Slug')" placeholder="my-entry-slug" required size="sm" />

            <flux:input type="number" wire:model="order" :label="__('Order')" placeholder="1" size="sm" />

            <flux:input type="datetime-local" wire:model="entryDate" :label="__('Date')" required size="sm" />

            <flux:radio.group wire:model="status" :label="__('Status')">
                @foreach ($statuses as $value => $label)
                    <flux:radio value="{{ $value }}" label="{{ $label }}" />
                @endforeach
            </flux:radio.group>
        </div>
    </form>

    @livewire ("media::asset-picker")
</section>
