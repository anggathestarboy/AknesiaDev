<?php

namespace App\Modules\Globals\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The single editable record of field values for a global set (one per
 * locale — locale is currently always null, see docs/AI-CONTEXT.md).
 *
 * @property int $id
 * @property string $handle
 * @property string|null $locale
 * @property array $data
 */
class GlobalSetVariable extends Model
{
    protected $table = 'global_set_variables';

    protected $fillable = ['handle', 'locale', 'data'];

    protected $casts = [
        'data' => 'array',
    ];
}
