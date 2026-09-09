<section class="w-full max-w-6xl space-y-6">
    <div class="">
        <flux:link :href="route('taxonomies.terms', $taxonomy->handle)" variant="subtle" wire:navigate class="text-sm">{{ __("← :taxonomy terms", ["taxonomy" => $taxonomy->title]) }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $editingId ? __("Edit term") : __("New term") }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid grid-cols-3 gap-6">
        <div class="col-span-2 space-y-6">
            @if (count($this->blueprintTabs) > 1)
                <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
                    @foreach ($this->blueprintTabs as $tab)
                        <button type="button" wire:click="setActiveTab('{{ $tab['handle'] }}')" class="border-b-2 px-3 py-2 text-sm {{ $activeTab === $tab['handle'] ? 'border-zinc-800 font-medium text-zinc-900 dark:border-zinc-100 dark:text-zinc-100' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' }}">{{ $tab["display"] }}</button>
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
                            <flux:label
                                >{{ $field["display"] }}
                                @if ($field["required"] ?? false)
                                    <span class="text-red-500">*</span>
                                @endif
                            </flux:label>
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
                                "bardValue" => $field["type"] === "bard" ? $data[$field["handle"]] ?? "" : null
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
                                        <flux:label
                                            >{{ $field["display"] }}
                                            @if ($field["required"] ?? false)
                                                <span class="text-red-500">*</span>
                                            @endif
                                        </flux:label>
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
                                            "bardValue" => $field["type"] === "bard" ? $data[$field["handle"]] ?? "" : null
                                        ])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach
        </div>

        <div class="col-span-1 space-y-6 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            @if (count($this->siteLinks) > 1)
                <div>
                    <flux:text class="text-xs font-medium text-zinc-500">{{ __("Site") }}</flux:text>
                    <div class="mt-1 flex flex-wrap gap-1">
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

            {{-- @if ($this->availableBlueprints->count() > 1)
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
            @endif --}}

            <flux:field>
                <flux:label>{{ __("Title") }} <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="title" placeholder="{{ __('Term title') }}" maxlength="300" required size="sm" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __("Slug") }} <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="slug" required placeholder="my-term-slug" size="sm" />
            </flux:field>

            <div class="flex justify-end gap-2 pt-2">
                <flux:button :href="route('taxonomies.terms', $taxonomy->handle)" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </div>
    </form>

    @livewire ("media::asset-picker")
</section>
