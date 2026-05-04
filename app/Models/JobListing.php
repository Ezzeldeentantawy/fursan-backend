<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class JobListing extends Model
{
    use HasFactory, SoftDeletes, HasTranslations;

    protected $table = 'job_listings';

    public $translatable = ['title', 'location', 'details', 'job_description', 'requirements', 'benefits', 'overview'];

    public const TYPE_ONSITE = 'onsite';
    public const TYPE_REMOTE = 'remote';

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'employer_id',
        'type',
        'is_easy_apply',
        'apply_link',
        'location',
        'title',
        'details',
        'job_description',
        'requirements',
        'benefits',
        'overview',
        'image',
        'status',
    ];

    protected $casts = [
        'type'   => 'string',
        'status' => 'string',
    ];

    /**
     * The employer (user with role=employer) who owns this job.
     */
    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }
}
