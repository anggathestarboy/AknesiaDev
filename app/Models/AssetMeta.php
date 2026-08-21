<?php

namespace App\Models;

use App\Models\Concerns\HasFlexibleData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class AssetMeta extends Model
{
    // use HasFlexibleData;

    protected $table = 'assets_meta';

    protected string $dataColumn = 'meta';

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

    public function container(): BelongsTo
    {
        return $this->belongsTo(AssetContainer::class, 'container', 'handle');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->container?->disk ?? 'public')
            ->url($this->path);
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
}
