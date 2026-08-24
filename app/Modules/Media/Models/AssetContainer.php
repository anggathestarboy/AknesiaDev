<?php

namespace App\Modules\Media\Models;

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
}
