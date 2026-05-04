<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\JobListing;
use App\Models\JobApply;

#[Fillable(['name', 'email', 'password', 'role', 'avatar', 'cv', 'phone', 'site_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_EMPLOYER = 'employer';
    public const ROLE_CANDIDATE = 'candidate';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function jobListings()
    {
        return $this->hasMany(JobListing::class, 'employer_id');
    }

    /**
     * Applications submitted by this candidate.
     */
    public function jobApplications()
    {
        return $this->hasMany(JobApply::class, 'candidate_id');
    }

    /**
     * Applications received by this employer.
     */
    public function receivedApplications()
    {
        return $this->hasMany(JobApply::class, 'employer_id');
    }

    /**
     * Get the site that the user belongs to.
     */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user can access the given site.
     */
    public function canAccessSite(int $siteId): bool
    {
        // Admin can access any site
        if ($this->isAdmin()) {
            return true;
        }

        // Users without site_id can't access any site (except admin)
        if (!$this->site_id) {
            return false;
        }

        // User can only access their assigned site
        return $this->site_id === $siteId;
    }
}
