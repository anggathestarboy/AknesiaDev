<?php

namespace App\Modules\Navigation\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $handle
 * @property string $title
 * @property array|null $settings
 */
class Navigation extends Model
{
    protected $table = 'navigations';

    protected $fillable = ['handle', 'title', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'handle';
    }
}
