<?php

namespace App\Modules\Blueprints\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $handle
 * @property array $data
 */
class Fieldset extends Model
{
    protected $table = 'fieldsets';

    protected $fillable = ['handle', 'data'];

    protected $casts = [
        'data' => 'array',
    ];
}
