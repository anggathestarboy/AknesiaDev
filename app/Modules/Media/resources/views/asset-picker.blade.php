<div x-data="{}">
    <flux:modal name="asset-picker" class="max-w-4xl space-y-6">
        <div class="flex items-center justify-between gap-4">
            <flux:heading size="lg">{{ __('Select assets') }}</flux:heading>
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search...') }}" size="sm" class="max-w-xs" />
        </div>

        @if ($search === '')
            <flux:breadcrumbs>
                <flux:breadcrumbs.item class="cursor-pointer" wire:click="goToFolder('')" :current="$folder === ''">{{ __('Home') }}</flux:breadcrumbs.item>
                @foreach ($this->breadcrumbs as $crumb)
                    <flux:breadcrumbs.item class="cursor-pointer" wire:click="goToFolder('{{ $crumb['path'] }}')" :current="$crumb['path'] === $folder">{{ $crumb['name'] }}</flux:breadcrumbs.item>
                @endforeach
            </flux:breadcrumbs>
        @endif

        <div class="max-h-96 overflow-y-auto">
            <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 md:grid-cols-6">
                @foreach ($this->listing['folders'] as $folderItem)
                    <button type="button" wire:click="goToFolder('{{ $folderItem['path'] }}')" class="flex flex-col items-center gap-1 rounded-lg p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                        <flux:icon.folder class="size-10 text-blue-400" />
                        <span class="w-full truncate text-center text-xs">{{ $folderItem['name'] }}</span>
                    </button>
                @endforeach

                @foreach ($this->listing['files'] as $asset)
                    <button
                        type="button"
                        wire:click="toggle({{ $asset->id }})"
                        wire:key="asset-{{ $asset->id }}"
                        class="relative flex flex-col gap-1 rounded-lg border p-1.5 text-left {{ $this->isSelected($asset->id) ? 'border-blue-500 ring-2 ring-blue-500' : 'border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800' }}"
                    >
                        <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded bg-zinc-100 dark:bg-zinc-800">
                            @include('media::partials._thumbnail', ['asset' => $asset])
                        </div>
                        <span class="w-full truncate text-center text-xs">{{ $asset->basename }}</span>
                        @if ($this->isSelected($asset->id))
                            <flux:icon.check-circle class="absolute right-1 top-1 size-4 rounded-full bg-white text-blue-500 dark:bg-zinc-900" />
                        @endif
                    </button>
                @endforeach

                @if ($this->listing['folders']->isEmpty() && $this->listing['files']->isEmpty())
                    <div class="col-span-full py-12 text-center text-zinc-400">{{ __('Nothing here yet.') }}</div>
                @endif
            </div>
        </div>

        @error('uploads.*')
            <flux:text class="text-red-500">{{ $message }}</flux:text>
        @enderror

        <div class="flex items-center justify-between border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <div>
                <input type="file" class="hidden" x-ref="assetPickerFileInput" multiple wire:model="uploads" />
                <flux:button type="button" size="sm" icon="arrow-up-tray" x-on:click="$refs.assetPickerFileInput.click()">{{ __('Upload') }}</flux:button>
            </div>

            <div class="flex items-center gap-2">
                <flux:text class="text-sm text-zinc-500">{{ count($selectedIds) }} {{ __('selected') }}</flux:text>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button size="sm" variant="primary" wire:click="confirm">{{ __('Use selected') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
