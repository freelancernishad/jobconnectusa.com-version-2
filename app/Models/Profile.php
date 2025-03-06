<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone_number',
        'address',
        'date_of_birth',
        'profile_picture',
        'preferred_job_title',
        'is_other_preferred_job_title',
        'company_name',
        'description',
        'years_of_experience_in_the_industry',
        'preferred_work_state',
        'preferred_work_zipcode',
        'years_of_experience_in_the_industry',
        'job_by',
        'activation_payment_made',
        'activation_payment_cancel',
        'your_experience',
        'familiar_with_safety_protocols',
        'resume',
        'status',
        'step', // Add 'step' here
        'status', // 'status' already exists, no change needed
        'profile_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with Service for preferred job title
    public function preferredJobTitleService()
    {
        return $this->belongsTo(Service::class, 'preferred_job_title', 'id');
    }

    public function getPreferredJobTitleAttribute()
    {
        if ($this->attributes['is_other_preferred_job_title']) {
            return $this->attributes['preferred_job_title'];
        }
        $service = Service::find($this->attributes['preferred_job_title']);
        return $service ? $service->name : null;
    }


}
