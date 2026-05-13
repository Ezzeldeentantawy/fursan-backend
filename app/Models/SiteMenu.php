<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteMenu extends Model
{
    protected $fillable = ['site_id', 'menus'];
    
    protected $casts = [
        'menus' => 'array',
    ];
    
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
