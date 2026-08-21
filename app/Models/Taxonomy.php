<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Taxonomy extends Model
{
    protected $fillable = ['handle', 'title', 'sites', 'settings'];

    protected $casts = [
        'sites' => 'array',
        'settings' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'handle';
    }

    public function terms(): HasMany
    {
        return $this->hasMany(TaxonomyTerm::class, 'taxonomy', 'handle');
    }
}
