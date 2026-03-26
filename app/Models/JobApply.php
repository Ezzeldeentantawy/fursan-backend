<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\JobListing;

class JobApply extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'employer_id',
        'candidate_id',
        'status',
        'cover_letter',
    ];

    /**
     * The job listing this application belongs to.
     */
    public function job()
    {
        return $this->belongsTo(JobListing::class, 'job_id');
    }

    /**
     * The employer (job owner) related to this application.
     */
    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    /**
     * The candidate who applied.
     */
    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}
