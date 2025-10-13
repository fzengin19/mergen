<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Research extends Model
{
    protected $fillable = ['user_id', 'title', 'additional_info'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
