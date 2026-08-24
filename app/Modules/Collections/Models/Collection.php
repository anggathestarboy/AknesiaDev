<?php

namespace App\Modules\Collections\Models;

use App\Modules\Blueprints\Models\Blueprint;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $handle
 * @property string $title
 * @property array|null $settings
 */
class Collection extends Model
{
    protected $table = 'collections';

    protected $fillable = ['handle', 'title', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    /**
     * The blueprint this collection's entries use. Prefers the explicit
     * pick from the Collection form (settings.blueprint), and falls back
     * to a blueprint namespaced to this collection (e.g. a blueprint with
     * namespace "collections.pages" for the "pages" collection) — matching
     * how the Blueprint form's "Namespace" dropdown already scopes a
     * blueprint to a collection.
     */
    public function effectiveBlueprint(): ?Blueprint
    {
        $explicitHandle = $this->settings['blueprint'] ?? null;

        if ($explicitHandle) {
            return Blueprint::where('handle', $explicitHandle)->first();
        }

        return Blueprint::where('namespace', "collections.{$this->handle}")->first();
    }
}
