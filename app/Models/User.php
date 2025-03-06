<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'email_verified_at',
        'email_verification_hash',
        'otp',
        'otp_expires_at',
        'profile_picture',
        'stripe_customer_id',
        'active_profile',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_hash',
        'email_verified_at',
        'otp',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];



      // Relationship with Profile
      public function profile()
      {
          return $this->hasOne(Profile::class);
      }

      // Get the active profile for the user
      public function activeProfile()
      {
          return $this->belongsTo(Profile::class, 'active_profile');
      }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => !is_null($this->email_verified_at),
        ];
    }





           // Relationship with Languages
    public function languages()
    {
        return $this->hasMany(Language::class);
    }

    // Relationship with Certifications
    public function certifications()
    {
        return $this->hasMany(Certification::class);
    }

    // Relationship with Skills
    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    // Relationship with Education
    public function education()
    {
        return $this->hasMany(Education::class);
    }

    // Relationship with Employment History
    public function employmentHistory()
    {
        return $this->hasMany(EmploymentHistory::class);
    }


    public function resumes()
    {
        return $this->hasMany(Resume::class);
    }

    public function resume()
    {
        return $this->hasOne(Resume::class)->latest('id');
    }


    public function userLookingServices()
    {
        return $this->hasMany(UserLookingService::class);
    }

    public function servicesLookingFor()
    {
        return $this->belongsToMany(Service::class, 'user_looking_services');
    }

    public function lookingServices()
    {
        return $this->hasMany(UserLookingService::class);
    }




        // Define the relationship for HiringAssignments
        public function hiringAssignments()
        {
            return $this->hasMany(HiringAssignment::class, 'assigned_employee_id');
        }

        // Define the relationship for employees in HiringAssignments
        public function assignedHiringAssignments()
        {
            return $this->hasMany(HiringAssignment::class, 'assigned_employee_id');
        }




        // Relationship with Service for preferred job title
        public function preferredJobTitleService()
        {
            return $this->belongsTo(Service::class, 'preferred_job_title', 'id');
        }

        public function getPreferredJobTitleAttribute()
        {
            // Check if 'is_other_preferred_job_title' is true
            if ($this->attributes['is_other_preferred_job_title']) {
                return $this->attributes['preferred_job_title'];
            }

            // Otherwise, retrieve the related service name
            $service = Service::find($this->attributes['preferred_job_title']);
            return $service ? $service->name : null;
        }

        // Browsing history for the user
        public function browsingHistory()
        {
            return $this->hasMany(BrowsingHistory::class, 'user_id');
        }

        // Users who have been viewed by this user
        public function viewedUsers()
        {
            return $this->hasMany(BrowsingHistory::class, 'viewed_user_id');
        }


        public function receivedLikes()
        {
            return $this->hasMany(Like::class, 'liked_user_id');
        }

        public function givenLikes()
        {
            return $this->hasMany(Like::class, 'user_id');
        }

        public function isLikedByUser(int $userId): bool
        {
            return $this->receivedLikes()->where('user_id', $userId)->exists();
        }

        public function thumbnail()
        {
            return $this->hasOne(Thumbnail::class);
        }


        // Relationship with HiringSelections
        public function hiringSelections()
        {
        return $this->hasMany(HiringSelection::class, 'employee_id'); // Adjust foreign key if needed
        }

        // Relationship with HiringRequests (if applicable)
        public function hiringRequests()
        {
        return $this->hasMany(HiringRequest::class, 'employer_id');
        }

        public function pendingHiring()
        {
        return HiringRequest::where('employer_id', $this->id)
        ->where('status', 'pending')
        ->with('selectedEmployees.employee') // Load the associated employees
        ->get();
        }


        public function hiredEmployees()
        {
        return HiringRequest::where('employer_id', $this->id)
        ->where('status', 'Assigned')
        ->with('hiringAssignments.employee') // Load the associated employees
        ->get();
        }

        public function got_hired()
        {
        return HiringAssignment::where('status', 'Assigned') // Ensure the assignment status is 'Assigned'
        ->where('assigned_employee_id', $this->id) // Adjust this to your actual job_id field
        ->with('hiringRequest.employer') // Load the associated employees
        ->get();
        }

        public function jobApplies()
        {
        return $this->hasMany(JobApply::class);
        }



}
