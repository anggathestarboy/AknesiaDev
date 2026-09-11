<section class="w-full space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __("Blueprints") }}</flux:heading>
            <flux:subheading>{{ __("Define field structures used by content.") }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button :href="route('blueprints.fieldsets')" wire:navigate>{{ __("Fieldsets") }}</flux:button>
            <flux:button variant="primary" :href="route('blueprints.create')" wire:navigate>{{ __("New blueprint") }}</flux:button>
        </div>
    </div>

    @if ($this->blueprints->isEmpty())
        <flux:text class="text-zinc-500">{{ __("No blueprints yet.") }}</flux:text>
    @else
        @php
            $categories = [
                "collections" => __("Collections"),
                "globals" => __("Globals"),
                "taxonomies" => __("Taxonomies"),
                "navigations" => __("Navigation"),
                "other" => __("Other"),
            ];
        @endphp

        @foreach ($categories as $categoryKey => $categoryLabel)
            @php $group = $this->blueprintGroups->get($categoryKey, collect()); @endphp
            @if ($group->isNotEmpty())
                <div class="space-y-3">
                    <flux:heading size="md">{{ $categoryLabel }}</flux:heading>

                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column class="">{{ __("Namespace") }}</flux:table.column>
                            <flux:table.column class="w-[400px]">{{ __("Handle") }}</flux:table.column>
                            <flux:table.column class="w-[100px]">{{ __("Fields") }}</flux:table.column>
                            <flux:table.column class="w-[150px]"></flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($group as $blueprint)
                                <flux:table.row wire:key="blueprint-{{ $blueprint->id }}">
                                    <flux:table.cell>{{ $blueprint->namespace ?? "—" }}</flux:table.cell>
                                    <flux:table.cell>{{ $blueprint->handle }}</flux:table.cell>
                                    <flux:table.cell>{{ count($blueprint->data["fields"] ?? []) }}</flux:table.cell>
                                    <flux:table.cell class="flex justify-end gap-2">
                                        <flux:button size="sm" :href="route('blueprints.edit', $blueprint)" wire:navigate>{{ __("Edit") }}</flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="delete({{ $blueprint->id }})" wire:confirm="{{ __('Delete this blueprint?') }}">{{ __("Delete") }}</flux:button>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            @endif
        @endforeach
    @endif
</section>
