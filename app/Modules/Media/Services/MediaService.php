<?php

namespace App\Modules\Media\Services;

use App\Modules\Media\Exceptions\MediaException;
use App\Modules\Media\Models\AssetContainer;
use App\Modules\Media\Models\AssetMeta;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class MediaService
{
    /**
     * @return array{folders: Collection<int, string>, files: Collection<int, AssetMeta>}
     */
    public function listFolder(string $container, string $path): array
    {
        $disk = $this->diskFor($container);

        $folders = collect(Storage::disk($disk)->directories($path))
            ->map(fn (string $dir) => basename($dir))
            ->sort()
            ->values();

        $files = AssetMeta::where('container', $container)
            ->where('folder', $path)
            ->orderBy('basename')
            ->get();

        return ['folders' => $folders, 'files' => $files];
    }

    public function createFolder(string $container, string $path): void
    {
        $disk = $this->diskFor($container);

        if (Storage::disk($disk)->exists($path)) {
            throw new MediaException("A folder or file already exists at \"{$path}\".");
        }

        Storage::disk($disk)->makeDirectory($path);
    }

    public function renameFolder(string $container, string $oldPath, string $newPath): void
    {
        $this->moveFolder($container, $oldPath, $newPath);
    }

    public function moveFolder(string $container, string $fromPath, string $toPath): void
    {
        $disk = $this->diskFor($container);

        if (Storage::disk($disk)->exists($toPath)) {
            throw new MediaException("A folder or file already exists at \"{$toPath}\".");
        }

        Storage::disk($disk)->move($fromPath, $toPath);

        $this->assetsWithinFolder($container, $fromPath)->each(function (AssetMeta $asset) use ($fromPath, $toPath) {
            $newFolder = $this->replacePrefix($asset->folder, $fromPath, $toPath);

            $asset->update([
                'folder' => $newFolder,
                'path' => trim($newFolder.'/'.$asset->basename, '/'),
            ]);
        });
    }

    public function deleteFolder(string $container, string $path): void
    {
        $disk = $this->diskFor($container);

        Storage::disk($disk)->deleteDirectory($path);

        $this->assetsWithinFolder($container, $path)->each->delete();
    }

    public function duplicateFolder(string $container, string $path): void
    {
        $this->copyFolderTo($container, $path, trim(dirname($path), '.'));
    }

    public function copyFolderTo(string $container, string $path, string $toParent): void
    {
        $disk = $this->diskFor($container);
        $newPath = $this->uniqueFolderName($container, $toParent, basename($path));

        foreach (Storage::disk($disk)->allFiles($path) as $file) {
            $relative = ltrim(Str::after($file, $path), '/');
            Storage::disk($disk)->copy($file, trim($newPath.'/'.$relative, '/'));
        }

        if (empty(Storage::disk($disk)->allFiles($path)) && empty(Storage::disk($disk)->directories($path))) {
            Storage::disk($disk)->makeDirectory($newPath);
        }

        $this->assetsWithinFolder($container, $path)->each(function (AssetMeta $asset) use ($path, $newPath) {
            $newFolder = $this->replacePrefix($asset->folder, $path, $newPath);
            $basename = $this->uniqueBasename($container, $newFolder, $asset->basename);

            AssetMeta::create([
                'container' => $asset->container,
                'folder' => $newFolder,
                'basename' => $basename,
                'filename' => pathinfo($basename, PATHINFO_FILENAME),
                'extension' => $asset->extension,
                'path' => trim($newFolder.'/'.$basename, '/'),
                'meta' => $asset->meta,
            ]);
        });
    }

    public function uploadFile(string $container, string $folder, UploadedFile $file): AssetMeta
    {
        $containerModel = $this->containerFor($container);
        $disk = $containerModel->disk;

        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = data_get($containerModel->settings, 'allowed_extensions');

        if (! empty($allowedExtensions) && ! in_array($extension, array_map('strtolower', $allowedExtensions), true)) {
            throw new MediaException("The extension \".{$extension}\" is not allowed in this container.");
        }

        $maxSizeKb = data_get($containerModel->settings, 'max_size_kb');

        if (! empty($maxSizeKb) && $file->getSize() > $maxSizeKb * 1024) {
            throw new MediaException("The file exceeds the maximum allowed size of {$maxSizeKb} KB.");
        }

        $basename = $this->uniqueBasename($container, $folder, $file->getClientOriginalName());
        $filename = pathinfo($basename, PATHINFO_FILENAME);

        Storage::disk($disk)->putFileAs($folder, $file, $basename);

        $meta = ['size' => $file->getSize(), 'mime_type' => $file->getMimeType()];

        if (str_starts_with((string) $file->getMimeType(), 'image/')) {
            $dimensions = @getimagesize($file->getRealPath());

            if ($dimensions) {
                $meta['width'] = $dimensions[0];
                $meta['height'] = $dimensions[1];
            }
        }

        return AssetMeta::create([
            'container' => $container,
            'folder' => $folder,
            'basename' => $basename,
            'filename' => $filename,
            'extension' => $extension,
            'path' => trim($folder.'/'.$basename, '/'),
            'meta' => $meta,
        ]);
    }

    public function duplicateFile(string $container, AssetMeta $asset): AssetMeta
    {
        return $this->copyFileTo($container, $asset, $asset->folder);
    }

    public function copyFileTo(string $container, AssetMeta $asset, string $toFolder): AssetMeta
    {
        $disk = $this->diskFor($container);
        $basename = $this->uniqueBasename($container, $toFolder, $asset->basename);
        $newPath = trim($toFolder.'/'.$basename, '/');

        Storage::disk($disk)->copy($asset->path, $newPath);

        return AssetMeta::create([
            'container' => $container,
            'folder' => $toFolder,
            'basename' => $basename,
            'filename' => pathinfo($basename, PATHINFO_FILENAME),
            'extension' => $asset->extension,
            'path' => $newPath,
            'meta' => $asset->meta,
        ]);
    }

    public function renameFile(string $container, AssetMeta $asset, string $newBasename): AssetMeta
    {
        if ($newBasename === $asset->basename) {
            return $asset;
        }

        if (AssetMeta::where('container', $container)->where('folder', $asset->folder)->where('basename', $newBasename)->where('id', '!=', $asset->id)->exists()) {
            throw new MediaException("A file named \"{$newBasename}\" already exists in this folder.");
        }

        $disk = $this->diskFor($container);
        $newPath = trim($asset->folder.'/'.$newBasename, '/');

        Storage::disk($disk)->move($asset->path, $newPath);

        $asset->update([
            'basename' => $newBasename,
            'filename' => pathinfo($newBasename, PATHINFO_FILENAME),
            'extension' => strtolower(pathinfo($newBasename, PATHINFO_EXTENSION)),
            'path' => $newPath,
        ]);

        return $asset;
    }

    public function moveFile(string $container, AssetMeta $asset, string $toFolder): AssetMeta
    {
        $disk = $this->diskFor($container);
        $newPath = trim($toFolder.'/'.$asset->basename, '/');

        if (AssetMeta::where('container', $container)->where('folder', $toFolder)->where('basename', $asset->basename)->where('id', '!=', $asset->id)->exists()) {
            throw new MediaException("A file named \"{$asset->basename}\" already exists in the destination folder.");
        }

        Storage::disk($disk)->move($asset->path, $newPath);

        $asset->update(['folder' => $toFolder, 'path' => $newPath]);

        return $asset;
    }

    public function deleteFile(string $container, AssetMeta $asset): void
    {
        Storage::disk($this->diskFor($container))->delete($asset->path);

        $asset->delete();
    }

    public function downloadFile(AssetMeta $asset): StreamedResponse
    {
        return Storage::disk($this->diskFor($asset->container))->download($asset->path, $asset->basename);
    }

    public function downloadFolder(string $container, string $path): BinaryFileResponse
    {
        $disk = $this->diskFor($container);
        $files = Storage::disk($disk)->allFiles($path);

        $tmp = tempnam(sys_get_temp_dir(), 'media-zip');
        $zip = new ZipArchive;
        $zip->open($tmp, ZipArchive::OVERWRITE);

        foreach ($files as $file) {
            $zip->addFromString(ltrim(Str::after($file, $path), '/'), Storage::disk($disk)->get($file));
        }

        $zip->close();

        $downloadName = (basename($path) ?: $container).'.zip';

        return response()->download($tmp, $downloadName)->deleteFileAfterSend();
    }

    protected function uniqueBasename(string $container, string $folder, string $desiredBasename): string
    {
        $filename = pathinfo($desiredBasename, PATHINFO_FILENAME);
        $extension = pathinfo($desiredBasename, PATHINFO_EXTENSION);
        $basename = $desiredBasename;
        $counter = 1;

        while (AssetMeta::where('container', $container)->where('folder', $folder)->where('basename', $basename)->exists()) {
            $basename = $extension ? "{$filename} ({$counter}).{$extension}" : "{$filename} ({$counter})";
            $counter++;
        }

        return $basename;
    }

    protected function uniqueFolderName(string $container, string $parent, string $desiredName): string
    {
        $disk = $this->diskFor($container);
        $name = $desiredName;
        $counter = 1;

        while (Storage::disk($disk)->exists(trim($parent.'/'.$name, '/'))) {
            $name = "{$desiredName} ({$counter})";
            $counter++;
        }

        return trim($parent.'/'.$name, '/');
    }

    protected function assetsWithinFolder(string $container, string $path): Collection
    {
        return AssetMeta::where('container', $container)
            ->where(function ($query) use ($path) {
                $query->where('folder', $path)->orWhere('folder', 'like', "{$path}/%");
            })
            ->get();
    }

    protected function replacePrefix(string $folder, string $from, string $to): string
    {
        if ($folder === $from) {
            return $to;
        }

        return $to.Str::after($folder, $from);
    }

    protected function containerFor(string $container): AssetContainer
    {
        return AssetContainer::where('handle', $container)->firstOrFail();
    }

    protected function diskFor(string $container): string
    {
        return $this->containerFor($container)->disk;
    }
}
