@if ($type === 'folder')
    <div
        wire:key="folder-{{ $path }}"
        class="group flex items-center gap-3 px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $this->isSelected('folder', $path) ? 'bg-blue-50 dark:bg-blue-950/40' : '' }}"
        x-data="{ dragOver: false }"
        draggable="true"
        wire:click="toggleSelect('folder', '{{ $path }}')"
        wire:dblclick="goToPath('{{ $path }}')"
        x-on:contextmenu.prevent.stop="contextMenu = 'folder-{{ $path }}'"
        x-on:dragstart="$event.dataTransfer.setData('text/plain', 'folder|{{ $path }}')"
        x-on:dragover="if ($event.dataTransfer.types.includes('Files')) return; $event.preventDefault(); dragOver = true"
        x-on:dragleave="dragOver = false"
        x-on:drop="if ($event.dataTransfer.types.includes('Files')) return; $event.preventDefault(); $event.stopPropagation(); dragOver = false; const raw = $event.dataTransfer.getData('text/plain'); const i = raw.indexOf('|'); $wire.dropOnFolder(raw.slice(0, i), raw.slice(i + 1), '{{ $path }}')"
        :class="dragOver ? 'ring-2 ring-blue-400 bg-blue-50 dark:bg-blue-950' : ''"
    >
        <div class="flex size-8 shrink-0 items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 80 66" class="size-full text-blue-400/90 group-hover:text-blue-400">
                <path fill="currentColor" d="M73.684 8.8h-40a2.1 2.1 0 0 1-1.516-.66L26.526 2.2A6.4 6.4 0 0 0 24.467.616 6.2 6.2 0 0 0 21.98 0H7.537C5.538 0 3.62.83 2.207 2.307.794 3.784 0 5.787 0 7.876v50.248c0 2.089.794 4.092 2.207 5.57C3.621 65.17 5.538 66 7.537 66h64.926c2 0 3.916-.83 5.33-2.307C79.206 62.216 80 60.213 80 58.124V15.4c0-1.75-.665-3.43-1.85-4.667C76.966 9.495 75.36 8.8 73.684 8.8"></path>
            </svg>
        </div>

        <span class="min-w-0 flex-1 truncate text-sm">{{ $name }}</span>
        <span class="hidden w-20 shrink-0 text-right text-sm text-zinc-400 sm:block">—</span>
        <span class="hidden w-36 shrink-0 text-right text-sm text-zinc-400 md:block">—</span>

        <div class="relative w-8 shrink-0">
            <div
                x-show="contextMenu === 'folder-{{ $path }}'"
                x-cloak
                x-on:click.outside="contextMenu = null"
                class="absolute top-0 right-0 z-10 w-40 rounded-md border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
            >
                <button type="button" wire:click.stop="startRenameFolder('{{ $path }}')" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Rename') }}</button>
                <button type="button" wire:click.stop="duplicateFolder('{{ $path }}')" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Duplicate') }}</button>
                <button type="button" wire:click.stop="cutFolder('{{ $path }}')" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Cut') }}</button>
                <button type="button" wire:click.stop="downloadFolder('{{ $path }}')" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Download') }}</button>
                <button type="button" wire:click.stop="askDeleteFolder('{{ $path }}')" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Delete') }}</button>
            </div>

            <button
                type="button"
                x-on:click.stop="contextMenu = contextMenu === 'folder-{{ $path }}' ? null : 'folder-{{ $path }}'"
                class="hidden rounded p-1 hover:bg-zinc-200 group-hover:block dark:hover:bg-zinc-700"
            >
                <flux:icon.ellipsis-vertical class="size-4" />
            </button>
        </div>
    </div>
@else
    <div
        wire:key="file-{{ $asset->id }}"
        class="group flex items-center gap-3 px-2 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-800 {{ $this->isSelected('file', (string) $asset->id) ? 'bg-blue-50 dark:bg-blue-950/40' : '' }}"
        draggable="true"
        wire:click="toggleSelect('file', '{{ $asset->id }}')"
        wire:dblclick="openPreview({{ $asset->id }})"
        x-on:contextmenu.prevent.stop="contextMenu = 'file-{{ $asset->id }}'"
        x-on:dragstart="$event.dataTransfer.setData('text/plain', 'file|{{ $asset->id }}')"
    >
        <div class="flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-sm bg-zinc-100 dark:bg-zinc-800">
            @include('media::partials._thumbnail', ['asset' => $asset])
        </div>

        <span class="min-w-0 flex-1 truncate text-sm">{{ $asset->basename }}</span>
        <span class="hidden w-20 shrink-0 text-right text-sm text-zinc-400 sm:block">{{ $asset->size ? \Illuminate\Support\Number::fileSize($asset->size) : '—' }}</span>
        <span class="hidden w-36 shrink-0 text-right text-sm text-zinc-400 md:block">{{ $asset->updated_at?->diffForHumans() ?? '—' }}</span>

        <div class="relative w-8 shrink-0">
            <div
                x-show="contextMenu === 'file-{{ $asset->id }}'"
                x-cloak
                x-on:click.outside="contextMenu = null"
                class="absolute top-0 right-0 z-10 w-40 rounded-md border border-zinc-200 bg-white py-1 shadow-lg dark:border-zinc-700 dark:bg-zinc-900"
            >
                <button type="button" wire:click.stop="openPreview({{ $asset->id }})" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('View / Edit') }}</button>
                <button type="button" wire:click.stop="startRenameFile({{ $asset->id }})" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Rename') }}</button>
                <button type="button" wire:click.stop="duplicateFile({{ $asset->id }})" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Duplicate') }}</button>
                <button type="button" wire:click.stop="cutFile({{ $asset->id }})" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Cut') }}</button>
                <button type="button" wire:click.stop="downloadFile({{ $asset->id }})" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Download') }}</button>
                <button type="button" wire:click.stop="deleteFile({{ $asset->id }})" x-on:click="contextMenu = null" class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('Delete') }}</button>
            </div>

            <button
                type="button"
                x-on:click.stop="contextMenu = contextMenu === 'file-{{ $asset->id }}' ? null : 'file-{{ $asset->id }}'"
                class="hidden rounded p-1 hover:bg-zinc-200 group-hover:block dark:hover:bg-zinc-700"
            >
                <flux:icon.ellipsis-vertical class="size-4" />
            </button>
        </div>
    </div>
@endif
