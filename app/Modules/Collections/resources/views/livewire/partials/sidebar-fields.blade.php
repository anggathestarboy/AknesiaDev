{{--
    Renders a blueprint's "sidebar" tab (handle exactly "sidebar" — see
    BlueprintForm's special badge for it) inside the right-hand sidebar
    column of a blueprint-driven form, instead of the main tabbed content
    area. Shared by Collections/entry-form, Taxonomies/term-form and
    Globals/global-set-form, which all have that same left-content +
    right-sidebar layout.

    Expects: $sidebarFields (top-level fields whose "tab" is "sidebar"),
    $sidebarSections (that tab's "sections" list), $data, $preview (bool).
--}}
@php
    $conditionTargets = $this->conditionTargetHandles($this->blueprintFields());
@endphp
@if ($sidebarFields->isNotEmpty())
    <div class="space-y-6 border-t border-zinc-200 pt-5 dark:border-zinc-700">
        @foreach ($sidebarFields->where('section', '') as $field)
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
                                "taxonomyIds" => $field["type"] === "taxonomy" ? $data[$field["handle"]] ?? [] : null,
                                "bardValue" => $field["type"] === "bard" ? $data[$field["handle"]] ?? "" : null,
                                "preview" => $preview ?? false,
                                "forceLive" => in_array($field["handle"], $conditionTargets, true)
                            ])
                    @endif
                </div>
            @endif
        @endforeach

        @foreach ($sidebarSections as $section)
            @php $sectionFields = $sidebarFields->where('section', $section['handle']); @endphp
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
                                                "taxonomyIds" => $field["type"] === "taxonomy" ? $data[$field["handle"]] ?? [] : null,
                                                "bardValue" => $field["type"] === "bard" ? $data[$field["handle"]] ?? "" : null,
                                                "preview" => $preview ?? false,
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
@endif
