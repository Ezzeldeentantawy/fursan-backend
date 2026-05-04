<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title', 
        'content',
        'type',
        'is_published'
    ];

    // This converts JSON columns to PHP arrays automatically
    protected $casts = [
        'content' => 'array',
        'is_published' => 'boolean',
    ];
}
