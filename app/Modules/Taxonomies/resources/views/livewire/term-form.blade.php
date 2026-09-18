<section class="w-full max-w-8xl space-y-6" x-on:keydown.window.ctrl.s.prevent="$wire.save()" x-on:keydown.window.meta.s.prevent="$wire.save()">
    <div class="">
        {{-- <flux:link :href="route('taxonomies.terms', $taxonomy->handle)" variant="subtle" wire:navigate class="text-sm">{{ __("← :taxonomy terms", ["taxonomy" => $taxonomy->title]) }}</flux:link> --}}
        <div class="flex gap-3 items-center border border-gray-200 py-2 px-3.5 rounded-xl">
            <div class="w-2.5 h-6 bg-sky-500 rounded-full"></div>
            <flux:heading size="xl">{{ $editingId ? __("Edit term") : __("New term") }}</flux:heading>
        </div>
    </div>

    @php
        $mainTabs = collect($this->blueprintTabs)->reject(fn($tab) => $tab["handle"] === "sidebar")->values();
        $sidebarTab = collect($this->blueprintTabs)->firstWhere("handle", "sidebar");
        $sidebarFields = collect($this->blueprintFields())->where("tab", "sidebar");
        $sidebarSections = $sidebarTab["sections"] ?? [];
        $conditionTargets = $this->conditionTargetHandles($this->blueprintFields());
    @endphp

    <form wire:submit="save" class="flex flex-col md:flex-row items-start gap-7">
        <div class="w-full lg:w-[60%] xl:w-[70%] space-y-6">
            @if ($mainTabs->count() > 1)
                <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700">
                    @foreach ($mainTabs as $tab)
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
                @if ($this->fieldIsVisible($field, $data))
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
                                    "bardValue" => $field["type"] === "bard" ? $data[$field["handle"]] ?? "" : null,
                                    "forceLive" => in_array($field["handle"], $conditionTargets, true)
                                ])
                        @endif
                    </div>
                @endif
            @empty
                @if ($tabFields->isEmpty())
                    <flux:text class="text-zinc-500"> {{ count($this->blueprintFields()) === 0 ? __("This blueprint has no fields yet.") : __("No fields in this tab.") }} </flux:text>
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
                                @if ($this->fieldIsVisible($field, $data))
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
                                                    "bardValue" => $field["type"] === "bard" ? $data[$field["handle"]] ?? "" : null,
                                                    "forceLive" => in_array($field["handle"], $conditionTargets, true)
                                                ])
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                @endif
            @endforeach
        </div>

        <div class="w-full lg:w-[40%] xl:w-[30%] space-y-6 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex gap-2">
                <flux:button variant="primary" class="w-full" type="submit">{{ __("Save") }}</flux:button>
                <flux:button :href="route('taxonomies.terms', $taxonomy->handle)" wire:navigate variant="filled">{{ __("Cancel") }}</flux:button>
            </div>

            @if (count($this->siteLinks) > 1)
                <div class="border border-gray-300 p-2 rounded-xl">
                    <div class="grid gap-1">
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
                <div class="grid grid-cols-[1fr_auto] items-end gap-2">
                    <flux:input wire:model="slug" class="min-w-0 w-full" required placeholder="my-term-slug" size="sm" />
                    <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateSlug" :tooltip="__('Regenerate from title')" />
                </div>
            </flux:field>

            @include ("collections::livewire.partials.sidebar-fields", ["sidebarFields" => $sidebarFields, "sidebarSections" => $sidebarSections, "data" => $data, "preview" => false])
        </div>
    </form>

    @livewire ("media::asset-picker")
</section>
