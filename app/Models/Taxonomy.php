<?php

namespace App\Models;

use App\Modules\Blueprints\Models\Blueprint;
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

    /**
     * The blueprint this taxonomy's terms use. Prefers the explicit pick
     * from the Taxonomy form (settings.blueprint), and falls back to a
     * blueprint namespaced to this taxonomy (e.g. "taxonomies.news_categories"),
     * mirroring Collection::effectiveBlueprint().
     */
    public function effectiveBlueprint(): ?Blueprint
    {
        $explicitHandle = $this->settings['blueprint'] ?? null;

        if ($explicitHandle) {
            return Blueprint::where('handle', $explicitHandle)->first();
        }

        return Blueprint::where('namespace', "taxonomies.{$this->handle}")->first();
    }
}
