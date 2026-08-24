<?php

namespace App\Modules\Blueprints\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $namespace
 * @property string $handle
 * @property array $data
 */
class Blueprint extends Model
{
    protected $table = 'blueprints';

    protected $fillable = ['namespace', 'handle', 'data'];

    protected $casts = [
        'data' => 'array',
    ];
}
