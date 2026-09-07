<section class="w-full max-w-5xl space-y-6">
    <div>
        <flux:link :href="route('globals.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Globals") }}</flux:link>
        <flux:heading size="lg" class="mt-5">{{ $globalSet->title }}</flux:heading>
    </div>

    <form wire:submit="save" class="grid grid-cols-1 gap-6 @if (count($this->siteLinks) > 0) md:grid-cols-3 @endif">
        <div class="space-y-6 @if (count($this->siteLinks) > 0) md:col-span-2 @endif">
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
                                "linkValue" => $field["type"] === "link" ? $data[$field["handle"]] ?? "" : null
                            ])
                    @endif
                </div>
            @empty
                @if ($tabFields->isEmpty())
                    @if (count($this->blueprintFields()) === 0)
                        <div class="rounded-lg border border-dashed border-zinc-300 p-6 text-center dark:border-zinc-600">
                            <flux:text class="text-zinc-500">{{ __("This blueprint has no fields yet.") }}</flux:text>
                            <div class="mt-3">
                                <flux:button size="sm" :href="route('blueprints.index')" wire:navigate>{{ __("Add fields in Blueprints") }}</flux:button>
                            </div>
                        </div>
                    @else
                        <flux:text class="text-zinc-500">{{ __("No fields in this tab.") }}</flux:text>
                    @endif
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
                                            "linkValue" => $field["type"] === "link" ? $data[$field["handle"]] ?? "" : null
                                        ])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endforeach

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                <flux:button :href="route('globals.index')" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
                <flux:button variant="primary" type="submit">{{ __("Save") }}</flux:button>
            </div>
        </div>

        @if (count($this->siteLinks) > 0)
            <div class="space-y-3 md:col-span-1">
                <flux:text class="text-xs font-medium text-zinc-500">{{ __("Language") }}</flux:text>
                <div class="flex flex-col gap-1 rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                    @foreach ($this->siteLinks as $siteLink)
                        @if ($siteLink["active"])
                            <flux:button size="sm" variant="primary" disabled class="justify-start">{{ $siteLink["name"] }}</flux:button>
                        @else
                            <flux:button size="sm" variant="filled" :href="$siteLink['href']" wire:navigate class="justify-start">{{ $siteLink["name"] }}</flux:button>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </form>

    @livewire ("media::asset-picker")
</section>
