<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimamLayout extends Model
{
    protected $fillable = [
        'key',
        'name',
        'module',
        'generation_type',
        'order_index',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order_index' => 'integer',
    ];
}
