<?php

namespace App\Modules\Media\Livewire;

use App\Modules\Media\Exceptions\MediaException;
use App\Modules\Media\Services\MediaService;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Global, reusable asset picker modal: any component can open it by
 * dispatching "open-asset-picker" (path, container, selected) and listening
 * for "assets-selected" (path, ids) to receive the result. Renders once per
 * page (e.g. included in EntryForm's view) and serves every "assets" field
 * on that page, including ones nested inside replicators.
 */
class AssetPicker extends Component
{
    use WithFileUploads;

    /**
     * The field path (see EntryForm::dataAt()) the current selection should
     * be dispatched back to once confirmed.
     *
     * @var array<int, string|int>|null
     */
    public ?array $targetPath = null;

    public string $container = '';

    /**
     * @var array<int, int>
     */
    public array $selectedIds = [];

    public string $folder = '';

    public string $search = '';

    public $uploads = [];

    /**
     * @param  array<int, string|int>  $path
     * @param  array<int, int>  $selected
     */
    #[On('open-asset-picker')]
    public function open(array $path, string $container, array $selected = []): void
    {
        $this->targetPath = $path;
        $this->container = $container;
        $this->selectedIds = array_map('intval', $selected);
        $this->folder = '';
        $this->search = '';

        unset($this->listing);

        Flux::modal('asset-picker')->show();
    }

    #[Computed]
    public function listing(): array
    {
        if ($this->container === '') {
            return ['folders' => collect(), 'files' => collect()];
        }

        $service = app(MediaService::class);

        if ($this->search !== '') {
            return $service->search($this->container, $this->search);
        }

        $listing = $service->listFolder($this->container, $this->folder);

        $listing['folders'] = $listing['folders']->map(fn (string $name): array => [
            'name' => $name,
            'path' => trim($this->folder.'/'.$name, '/'),
        ]);

        return $listing;
    }

    #[Computed]
    public function breadcrumbs(): array
    {
        if ($this->folder === '') {
            return [];
        }

        $crumbs = [];
        $accumulated = '';

        foreach (explode('/', $this->folder) as $segment) {
            $accumulated = trim($accumulated.'/'.$segment, '/');
            $crumbs[] = ['name' => $segment, 'path' => $accumulated];
        }

        return $crumbs;
    }

    public function goToFolder(string $path): void
    {
        $this->folder = $path;
        $this->search = '';
        unset($this->listing);
    }

    public function toggle(int $assetId): void
    {
        $this->selectedIds = $this->isSelected($assetId)
            ? array_values(array_diff($this->selectedIds, [$assetId]))
            : [...$this->selectedIds, $assetId];
    }

    public function isSelected(int $assetId): bool
    {
        return in_array($assetId, $this->selectedIds, true);
    }

    public function confirm(): void
    {
        $this->dispatch('assets-selected', path: $this->targetPath, ids: $this->selectedIds);

        Flux::modal('asset-picker')->close();
    }

    /**
     * Uploads land in the same container/folder being browsed via the
     * shared MediaService, so they show up in the full Media Library too —
     * and are auto-selected here to save the extra click.
     */
    public function updatedUploads(): void
    {
        if (empty($this->uploads) || $this->container === '') {
            return;
        }

        $this->validate(['uploads.*' => ['required', 'file', 'max:51200']]);

        $service = app(MediaService::class);
        $failures = [];

        foreach ($this->uploads as $file) {
            try {
                $asset = $service->uploadFile($this->container, $this->folder, $file);
                $this->selectedIds[] = $asset->id;
            } catch (MediaException $e) {
                $failures[] = $e->getMessage();
            }
        }

        $this->uploads = [];
        unset($this->listing);

        if ($failures) {
            Flux::toast(text: implode(' ', $failures), variant: 'danger');
        } else {
            Flux::toast(text: __('Upload complete.'), variant: 'success');
        }
    }

    public function render(): View
    {
        return view('media::asset-picker');
    }
}
