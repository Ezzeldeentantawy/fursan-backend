<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSocialMedia extends Model
{
    protected $fillable = ['site_id', 'social_links'];
    
    protected $casts = [
        'social_links' => 'array',
    ];
    
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
