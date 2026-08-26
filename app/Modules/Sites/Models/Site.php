<?php

namespace App\Modules\Sites\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $handle
 * @property string $name
 * @property string $url
 * @property string $locale
 * @property string $lang
 * @property array $attributes
 * @property int $order
 */
class Site extends Model
{
    protected $table = 'sites';

    protected $fillable = ['handle', 'name', 'url', 'locale', 'lang', 'attributes', 'order'];

    protected $casts = [
        'attributes' => 'array',
    ];
}
