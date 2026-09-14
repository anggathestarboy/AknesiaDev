<section class="w-full space-y-6" x-data="{ deleteFormId: null }">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="lg">{{ __('Forms') }}</flux:heading>
            <flux:subheading>{{ __('Front-end HTML forms — you build the markup, this defines the fields and stores submissions.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" wire:click="create">{{ __('New form') }}</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('Handle') }}</flux:table.column>
            <flux:table.column>{{ __('Blueprint') }}</flux:table.column>
            <flux:table.column>{{ __('Submissions') }}</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->forms as $form)
                <flux:table.row wire:key="form-{{ $form->id }}">
                    <flux:table.cell>{{ $form->title }}</flux:table.cell>
                    <flux:table.cell>{{ $form->handle }}</flux:table.cell>
                    <flux:table.cell>{{ $form->effectiveBlueprint()?->handle ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $form->submissions()->count() }}</flux:table.cell>
                    <flux:table.cell class="flex justify-end gap-2">
                        <flux:button size="sm" :href="route('forms.submissions', $form->handle)" wire:navigate>{{ __('Submissions') }}</flux:button>
                        <flux:button size="sm" wire:click="edit({{ $form->id }})">{{ __('Settings') }}</flux:button>
                        <flux:modal.trigger name="confirm-delete-form">
                            <flux:button size="sm" variant="danger" type="button" x-on:click="deleteFormId = {{ $form->id }}">{{ __('Delete') }}</flux:button>
                        </flux:modal.trigger>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="5">{{ __('No forms yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="form-form" class="max-w-lg" :show="$errors->isNotEmpty()">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit form') : __('New form') }}</flux:heading>

            <flux:input wire:model="title" :label="__('Title')" placeholder="Contact" size="sm" required autofocus />

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <flux:input wire:model="handle" :label="__('Handle')" size="sm" placeholder="contact" required />
                </div>
                <flux:button size="sm" type="button" icon="arrow-path" wire:click="regenerateHandle" :tooltip="__('Regenerate from title')" />
            </div>

            @unless ($createBlueprint)
                <flux:select wire:model.live="blueprint" size="sm" :label="__('Blueprint')" placeholder="{{ __('No blueprint') }}">
                    @foreach ($this->availableBlueprints as $availableBlueprint)
                        <flux:select.option value="{{ $availableBlueprint->handle }}"> {{ $availableBlueprint->namespace ? "{$availableBlueprint->namespace}.{$availableBlueprint->handle}" : $availableBlueprint->handle }} </flux:select.option>
                    @endforeach
                </flux:select>

                @if ($this->availableBlueprints->isEmpty())
                    <flux:text class="text-zinc-500">{{ __('No blueprints yet — create one in the Blueprints module first.') }}</flux:text>
                @endif

                @if ($this->selectedBlueprintFields !== [])
                    <flux:checkbox.group wire:model="previewFields" :label="__('Preview columns')" :description="__('Which fields show as columns in the submissions table. Leave all unchecked to show every field.')">
                        @foreach ($this->selectedBlueprintFields as $field)
                            <flux:checkbox value="{{ $field['handle'] }}" label="{{ $field['display'] ?: $field['handle'] }}" />
                        @endforeach
                    </flux:checkbox.group>
                @endif
            @endunless

            @unless ($editingId)
                <flux:checkbox wire:model.live="createBlueprint" :label="__('Create a blueprint for this form')" :description="__('Recommended — you\'ll add its fields right after saving. Turn off to pick an existing blueprint instead.')" />
            @endunless

            <flux:switch wire:model="storeSubmissions" :label="__('Store submissions')" :description="__('Save every successful submission to the Submissions table below.')" />

            <flux:input wire:model="redirectUrl" :label="__('Redirect URL')" placeholder="https://example.com/thank-you" size="sm" :description="__('Where a successful submission redirects to. Leave blank to redirect back to the page the form was on.')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete-form" class="min-w-[24rem]">
        <div class="space-y-4">
            <flux:heading size="lg">{{ __('Delete this form?') }}</flux:heading>
            <flux:text class="text-zinc-500">{{ __('This cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:modal.close>
                    <flux:button variant="danger" type="button" x-on:click="$wire.delete(deleteFormId)">{{ __('Delete') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</section>
