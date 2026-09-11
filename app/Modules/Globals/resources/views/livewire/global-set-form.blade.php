<section class="w-full max-w-8xl space-y-6" x-on:keydown.window.ctrl.s.prevent="$wire.save()" x-on:keydown.window.meta.s.prevent="$wire.save()">
    <div>
        {{-- <flux:link :href="route('globals.index')" variant="subtle" wire:navigate class="text-sm">{{ __("← Globals") }}</flux:link> --}}
        <div class="flex gap-3 items-center border border-gray-200 py-2 px-3.5 rounded-xl">
            <div class="w-2.5 h-2.5 bg-sky-500 rounded-full"></div>
            <flux:heading size="xl">{{ $globalSet->title }}</flux:heading>
        </div>
    </div>

    <form wire:submit="save" class="flex flex-col md:flex-row items-start gap-7 @if (count($this->siteLinks) > 0) md:grid-cols-3 @endif">
        <div class="w-full lg:w-[60%] xl:w-[70%] space-y-6 @if (count($this->siteLinks) > 0) md:col-span-2 @endif">
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
                    <div class="px-2 py-2.5 rounded-2xl bg-gray-200/80">
                        <div class="font-semibold text-black px-2">
                            <flux:subheading>{{ $section["display"] }}</flux:subheading>
                        </div>

                        <div class="space-y-6 bg-white p-3 rounded-lg mt-3">
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
                    </div>
                @endif
            @endforeach
        </div>

        <div class="w-full lg:w-[40%] xl:w-[30%] space-y-6 border border-zinc-200 p-4 rounded-xl h-max">
            <div class="flex justify-end gap-2 dark:border-zinc-700 mb-6">
                <flux:button variant="primary" class="w-full" type="submit">{{ __("Save") }}</flux:button>
                <flux:button :href="route('globals.index')" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
            </div>

            @if (count($this->siteLinks) > 0)
                <div class="space-y-1 md:col-span-1 border border-gray-300 p-2 rounded-xl">
                    <div class="flex flex-col gap-1 dark:border-zinc-700">
                        @foreach ($this->siteLinks as $siteLink)
                            @if ($siteLink["active"])
                                <flux:button size="sm" variant="primary" disabled class="justify-center">{{ $siteLink["name"] }}</flux:button>
                            @else
                                <flux:button size="sm" variant="filled" :href="$siteLink['href']" wire:navigate class="justify-center">{{ $siteLink["name"] }}</flux:button>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </form>

    @livewire ("media::asset-picker")
</section>
