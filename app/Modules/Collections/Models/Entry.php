<?php

namespace App\Modules\Collections\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $site
 * @property string|null $origin_id
 * @property bool $published
 * @property string $status
 * @property string|null $slug
 * @property string|null $uri
 * @property string|null $date
 * @property int|null $order
 * @property string $collection
 * @property string|null $blueprint
 * @property array $data
 */
class Entry extends Model
{
    use HasUuids;

    protected $table = 'entries';

    protected $fillable = [
        'site', 'origin_id', 'published', 'status', 'slug', 'uri', 'date', 'order', 'collection', 'blueprint', 'data',
    ];

    protected $casts = [
        'published' => 'boolean',
        'data' => 'array',
    ];
}
