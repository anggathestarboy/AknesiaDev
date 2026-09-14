<section class="w-full space-y-6" x-data="{ deleteSiteId: null }">
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
                        <flux:button size="sm" wire:click="edit({{ $site->id }})">{{ __("Settings") }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-site">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteSiteId = {{ $site->id }}">{{ __("Delete") }}</flux:button>
                        </flux:modal.trigger>
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
                <flux:input wire:model="name" :label="__('Name')" size="sm" placeholder="English" required autofocus />
                <div class="flex items-end gap-2">
                    <flux:input size="sm" wire:model="handle" :label="__('Handle')" placeholder="english" class="flex-1" required />
                    <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateHandle" :tooltip="__('Regenerate from name')" />
                </div>
                <flux:input size="sm" wire:model="url" :label="__('URL')" placeholder="https://example.com/" required />
                <flux:input size="sm" wire:model="locale" :label="__('Locale')" placeholder="en_US" required />
                <flux:field class="col-span-2">
                    <flux:label>{{ __("Language") }}</flux:label>
                    <div
                        x-data="{
                            search: '',
                            positionMenu() {
                                const rect = $refs.languageTrigger.getBoundingClientRect();
                                Object.assign($refs.languageMenu.style, {
                                    top: `${rect.bottom + 8}px`,
                                    left: `${rect.left}px`,
                                    width: `${rect.width}px`,
                                });
                            },
                        }"
                    >
                        <flux:button
                            size="sm"
                            x-ref="languageTrigger"
                            type="button"
                            class="w-full justify-between!"
                            icon:trailing="chevron-down"
                            x-on:click="
                                positionMenu();
                                $refs.languageMenu.togglePopover();
                            "
                        >
                            {{ $lang !== "" ? ($this->languageOptions[$lang] ?? $lang) . " ({$lang})" : __("Select a language") }}
                        </flux:button>

                        <div
                            x-ref="languageMenu"
                            popover
                            x-on:beforetoggle="
                                if ($event.newState === 'open') {
                                    search = '';
                                    positionMenu();
                                    $nextTick(() => $refs.languageSearchInput.focus());
                                }
                            "
                            class="fixed m-0 rounded-lg border border-zinc-200 bg-white p-1.25 shadow-lg dark:border-zinc-600 dark:bg-zinc-700"
                        >
                            <div class="mb-1 border-b border-zinc-200 pb-2 dark:border-zinc-600">
                                <flux:input x-ref="languageSearchInput" x-model="search" size="sm" icon="magnifying-glass" placeholder="{{ __('Search language...') }}" autocomplete="off" class:input="outline-none" />
                            </div>

                            <div class="max-h-64 overflow-y-auto">
                                @foreach ($this->languageOptions as $code => $languageName)
                                    <button
                                        type="button"
                                        wire:click="$set('lang', '{{ $code }}')"
                                        x-on:click="$refs.languageMenu.hidePopover()"
                                        x-show="search === '' || {{ \Illuminate\Support\Js::from(strtolower("{$languageName} {$code}")) }}.includes(search.toLowerCase())"
                                        class="flex w-full items-center rounded-md px-2 py-1.5 text-start text-sm font-medium text-zinc-800 hover:bg-zinc-50 dark:text-white dark:hover:bg-zinc-600"
                                    >
                                        {{ $languageName }} ({{ $code }})
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <flux:error name="lang" />
                </flux:field>
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

    <flux:modal name="confirm-delete-site" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __("Delete this site?") }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __("This cannot be undone.") }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __("Cancel") }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteSiteId)">{{ __("Delete") }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
