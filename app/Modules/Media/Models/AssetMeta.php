<?php

namespace App\Modules\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class AssetMeta extends Model
{
    protected $table = 'assets_meta';

    protected $fillable = [
        'container',
        'folder',
        'basename',
        'filename',
        'extension',
        'path',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function containerModel(): BelongsTo
    {
        return $this->belongsTo(AssetContainer::class, 'container', 'handle');
    }

    public function getUrlAttribute(): string
    {
        $disk = $this->containerModel?->disk ?? 'public';
        $scopedPath = AssetContainer::scopedPath($this->container, $this->path);

        if ($disk === 'public') {
            return Storage::disk($disk)->url($scopedPath);
        }

        // Non-public disks are served through Laravel's local disk serving route,
        // which validates a *relative* signature, so the URL must be signed as relative too.
        return URL::temporarySignedRoute('storage.'.$disk, now()->addMinutes(5), ['path' => $scopedPath], absolute: false);
    }

    public function getSizeAttribute()
    {
        return data_get($this->meta, 'size');
    }

    public function getMimeTypeAttribute()
    {
        return data_get($this->meta, 'mime_type');
    }

    public function getWidthAttribute()
    {
        return data_get($this->meta, 'width');
    }

    public function getHeightAttribute()
    {
        return data_get($this->meta, 'height');
    }

    public function getTitleAttribute()
    {
        return data_get($this->meta, 'data.title') ?: $this->basename;
    }

    public function getAltAttribute()
    {
        return data_get($this->meta, 'data.alt') ?: '';
    }

    public function getLoadingAttribute()
    {
        return data_get($this->meta, 'data.loading') ?: 'lazy';
    }
}
