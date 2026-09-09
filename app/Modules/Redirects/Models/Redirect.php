<?php

namespace App\Modules\Redirects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source
 * @property string $target
 * @property int $status_code
 * @property string $match_type
 * @property bool $is_active
 * @property int $hits
 * @property Carbon|null $start_at
 * @property Carbon|null $end_at
 * @property bool $is_external
 * @property bool $keep_path
 */
class Redirect extends Model
{
    protected $table = 'redirects';

    protected $fillable = [
        'source',
        'target',
        'status_code',
        'match_type',
        'is_active',
        'hits',
        'start_at',
        'end_at',
        'is_external',
        'keep_path',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_active' => 'boolean',
        'hits' => 'integer',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_external' => 'boolean',
        'keep_path' => 'boolean',
    ];
}
