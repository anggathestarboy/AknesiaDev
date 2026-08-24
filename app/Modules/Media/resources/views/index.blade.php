<div class="space-y-6 p-4 sm:p-6" x-data="{ contextMenu: null }" @click="contextMenu = null">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <flux:heading size="xl">{{ __('Media Library') }}</flux:heading>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button.group>
                    <flux:button class="cursor-pointer" size="sm" :variant="$viewMode === 'grid' ? 'primary' : 'ghost'" icon="squares-2x2" wire:click="$set('viewMode', 'grid')" />
                    <flux:button class="cursor-pointer" size="sm" :variant="$viewMode === 'list' ? 'primary' : 'ghost'" icon="list-bullet" wire:click="$set('viewMode', 'list')" />
                </flux:button.group>

                @if (!empty($clipboard))
                    <flux:button class="cursor-pointer" size="sm" variant="filled" icon="clipboard-document" wire:click="paste">{{ __('Paste') }}</flux:button>
                @endif

                <flux:button class="cursor-pointer" size="sm" icon="folder-plus" wire:click="startCreateFolder">{{ __('New Folder') }}</flux:button>
                <flux:button class="cursor-pointer" size="sm" variant="primary" icon="arrow-up-tray" x-on:click="$refs.fileInput.click()">{{ __('Upload') }}</flux:button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg bg-zinc-100 px-4 py-2 dark:bg-zinc-800">
            <flux:breadcrumbs class="w-max">
                <flux:breadcrumbs.item class="cursor-pointer" wire:click="goToPath('')" :current="$path === ''">{{ __('Home') }}</flux:breadcrumbs.item>
                @foreach ($this->breadcrumbs as $crumb)
                    <flux:breadcrumbs.item class="cursor-pointer" wire:click="goToPath('{{ $crumb['path'] }}')" :current="$crumb['path'] === $path">{{ $crumb['name'] }}</flux:breadcrumbs.item>
                @endforeach
            </flux:breadcrumbs>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <flux:input wire:model.live.debounce.400ms="search" size="sm" icon="magnifying-glass" :placeholder="__('Search files & folders')" class="w-full sm:w-72" />

            <flux:select class="cursor-pointer sm:w-56" wire:model.live="container" size="sm">
                <flux:select.option value="assets">{{ __('Assets') }}</flux:select.option>
                <flux:select.option value="private_library">{{ __('Private Library') }}</flux:select.option>
            </flux:select>
        </div>

        <div
            class="rounded-lg border border-dashed border-zinc-300 p-6 dark:border-zinc-700"
            x-data="{ dragging: false }"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
            :class="dragging ? 'border-blue-500 bg-blue-50 dark:bg-blue-950' : ''"
        >
            @if ($viewMode === 'grid')
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-9">
                    @forelse ($this->listing['folders'] as $folder)
                        @include('media::partials._grid-item', ['type' => 'folder', 'name' => $folder['name'], 'path' => $folder['path']])
                    @empty
                    @endforelse

                    @forelse ($this->listing['files'] as $file)
                        @include('media::partials._grid-item', ['type' => 'file', 'asset' => $file])
                    @empty
                    @endforelse

                    @if ($this->listing['folders']->isEmpty() && $this->listing['files']->isEmpty())
                        <div class="col-span-full py-12 text-center text-zinc-400">
                            {{ __('This folder is empty.') }}
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-1">
                    @if ($this->listing['folders']->isNotEmpty() || $this->listing['files']->isNotEmpty())
                        <div class="flex items-center gap-3 border-b border-zinc-200 px-2 pb-2 text-xs font-medium text-zinc-400 dark:border-zinc-700">
                            <span class="size-8 shrink-0"></span>
                            <span class="min-w-0 flex-1">{{ __('Name') }}</span>
                            <span class="hidden w-20 shrink-0 text-right sm:block">{{ __('Size') }}</span>
                            <span class="hidden w-36 shrink-0 text-right md:block">{{ __('Last Modified') }}</span>
                            <span class="w-8 shrink-0"></span>
                        </div>
                    @endif

                    @forelse ($this->listing['folders'] as $folder)
                        @include('media::partials._list-item', ['type' => 'folder', 'name' => $folder['name'], 'path' => $folder['path']])
                    @empty
                    @endforelse

                    @forelse ($this->listing['files'] as $file)
                        @include('media::partials._list-item', ['type' => 'file', 'asset' => $file])
                    @empty
                    @endforelse

                    @if ($this->listing['folders']->isEmpty() && $this->listing['files']->isEmpty())
                        <div class="py-12 text-center text-zinc-400">
                            {{ __('This folder is empty.') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        @error('uploads.*') <flux:text class="text-red-500">{{ $message }}</flux:text> @enderror

        <input type="file" class="hidden" x-ref="fileInput" multiple wire:model="uploads" />

        @if (!empty($selected))
            <div class="fixed inset-x-0 bottom-4 z-20 flex justify-center px-4 sm:bottom-6">
                <div class="flex max-w-full flex-wrap items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text>{{ count($selected) }} {{ __('selected') }}</flux:text>

                    @if (count($selected) === 1)
                        <flux:button class="cursor-pointer" size="sm" icon="pencil" wire:click="renameSelected">{{ __('Rename') }}</flux:button>
                    @endif

                    <flux:button class="cursor-pointer" size="sm" icon="clipboard-document" wire:click="copySelected">{{ __('Copy') }}</flux:button>
                    <flux:button class="cursor-pointer" size="sm" icon="scissors" wire:click="cutSelected">{{ __('Cut') }}</flux:button>
                    <flux:button class="cursor-pointer" size="sm" icon="document-duplicate" wire:click="duplicateSelected">{{ __('Duplicate') }}</flux:button>
                    <flux:button class="cursor-pointer" size="sm" variant="danger" icon="trash" wire:click="askDeleteSelected">{{ __('Delete') }}</flux:button>
                    <flux:button class="cursor-pointer" size="sm" variant="ghost" icon="x-mark" wire:click="clearSelection" />
                </div>
            </div>
        @endif

        <flux:modal name="create-folder" wire:model="showCreateFolder" class="space-y-6">
            <flux:heading size="lg">{{ __('New Folder') }}</flux:heading>
            <flux:input wire:model="newFolderName" :label="__('Folder name')" autofocus />
            <div class="flex justify-end gap-2">
                <flux:button class="cursor-pointer" variant="ghost" wire:click="$set('showCreateFolder', false)">{{ __('Cancel') }}</flux:button>
                <flux:button class="cursor-pointer" variant="primary" wire:click="createFolder">{{ __('Create') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="rename-folder" wire:model="showRenameFolder" class="space-y-6">
            <flux:heading size="lg">{{ __('Rename Folder') }}</flux:heading>
            <flux:input wire:model="renameFolderValue" :label="__('Folder name')" autofocus />
            <div class="flex justify-end gap-2">
                <flux:button class="cursor-pointer" variant="ghost" wire:click="$set('showRenameFolder', false)">{{ __('Cancel') }}</flux:button>
                <flux:button class="cursor-pointer" variant="primary" wire:click="renameFolder">{{ __('Rename') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="rename-file" wire:model="showRenameFile" class="space-y-6">
            <flux:heading size="lg">{{ __('Rename File') }}</flux:heading>
            <flux:input wire:model="renameFileValue" :label="__('File name')" autofocus />
            <div class="flex justify-end gap-2">
                <flux:button class="cursor-pointer" variant="ghost" wire:click="$set('showRenameFile', false)">{{ __('Cancel') }}</flux:button>
                <flux:button class="cursor-pointer" variant="primary" wire:click="renameFile">{{ __('Rename') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="delete-folder" wire:model="showDeleteFolder" class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Folder') }}</flux:heading>
            <flux:text>
                @if ($deleteFolderHasContents)
                    {{ __('This folder is not empty. Deleting it will permanently remove everything inside it. This cannot be undone.') }}
                @else
                    {{ __('This folder will be permanently deleted. This cannot be undone.') }}
                @endif
            </flux:text>
            <div class="flex justify-end gap-2">
                <flux:button class="cursor-pointer" variant="ghost" wire:click="$set('showDeleteFolder', false)">{{ __('Cancel') }}</flux:button>
                <flux:button class="cursor-pointer" variant="danger" wire:click="confirmDeleteFolder">{{ __('Delete') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="bulk-delete" wire:model="showBulkDelete" class="space-y-6">
            <flux:heading size="lg">{{ __('Delete Selected') }}</flux:heading>
            <flux:text>{{ __('This will permanently delete the selected items, including the contents of any selected folders. This cannot be undone.') }}</flux:text>
            <div class="flex justify-end gap-2">
                <flux:button class="cursor-pointer" variant="ghost" wire:click="$set('showBulkDelete', false)">{{ __('Cancel') }}</flux:button>
                <flux:button class="cursor-pointer" variant="danger" wire:click="confirmDeleteSelected">{{ __('Delete') }}</flux:button>
            </div>
        </flux:modal>

        <flux:modal name="preview" wire:model="showPreview" class="space-y-6">
            @if ($this->previewAsset)
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div class="flex aspect-square items-center justify-center rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
                        @include('media::partials._thumbnail', ['asset' => $this->previewAsset, 'fit' => 'object-contain'])
                    </div>

                    <div class="space-y-4">
                        <flux:heading size="lg">{{ $this->previewAsset->basename }}</flux:heading>

                        <flux:input wire:model="previewTitle" :label="__('Title')" />
                        <flux:input wire:model="previewAlt" :label="__('Alt text')" />

                        <flux:select class="cursor-pointer" wire:model="previewLoading" :label="__('Loading')">
                            <flux:select.option value="lazy">{{ __('Lazy') }}</flux:select.option>
                            <flux:select.option value="eager">{{ __('Eager') }}</flux:select.option>
                        </flux:select>

                        <div class="flex justify-end gap-2">
                            <flux:button class="cursor-pointer" variant="ghost" wire:click="$set('showPreview', false)">{{ __('Close') }}</flux:button>
                            <flux:button class="cursor-pointer" variant="primary" wire:click="savePreviewMeta">{{ __('Save') }}</flux:button>
                        </div>
                    </div>
                </div>
            @endif
        </flux:modal>
    </div>
