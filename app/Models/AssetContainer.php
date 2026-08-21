<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetContainer extends Model
{
    protected $fillable = ['handle', 'title', 'disk', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    public function assets(): HasMany
    {
        return $this->hasMany(AssetMeta::class, 'container', 'handle');
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
}
