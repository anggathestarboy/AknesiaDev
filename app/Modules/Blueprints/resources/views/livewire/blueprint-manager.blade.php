<section class="w-full space-y-6" x-data="{ deleteBlueprintId: null }">
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
                "forms" => __("Forms"),
                "other" => __("Other"),
            ];
        @endphp

        @foreach ($categories as $categoryKey => $categoryLabel)
            @php $group = $this->blueprintGroups->get($categoryKey, collect()); @endphp
            @if ($group->isNotEmpty())
                <div class="bg-gray-200/80 px-2 py-2 rounded-2xl">
                    <flux:heading size="md" class="pt-1 px-2 pb-2">{{ $categoryLabel }}</flux:heading>
                    <div class="space-y-3 bg-white px-3 py-1 rounded-xl">
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
                                            <flux:button size="xs" :href="route('blueprints.edit', $blueprint)" wire:navigate>{{ __("Edit") }}</flux:button>
                                            <flux:modal.trigger name="confirm-delete-blueprint">
                                                <flux:button size="xs" variant="danger" type="button" x-on:click="deleteBlueprintId = {{ $blueprint->id }}">{{ __("Delete") }}</flux:button>
                                            </flux:modal.trigger>
                                        </flux:table.cell>
                                    </flux:table.row>
                                @endforeach
                            </flux:table.rows>
                        </flux:table>
                    </div>
                </div>
            @endif
        @endforeach
    @endif

    <flux:modal name="confirm-delete-blueprint" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this blueprint?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteBlueprintId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
