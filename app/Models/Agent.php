<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'name',
        'background',
        'steps',
        'output',
        'node_class',
        'model',
        'apiKey',
        'provider',
        'is_active'
    ];

    protected $casts = [ 
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}
