<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'employer_id',
        'job_title',
        'job_description',
        'expected_start_date',
        'salary_offer',
        'status',
        'employee_needed',
        'hourly_rate',        // New attribute
        'total_hours',        // New attribute
        'paid_amount',        // New attribute
        'due_amount',         // New attribute
        'total_amount',       // New attribute
    ];

    // Relationship with Employer (User)
    public function employer()
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    // Define the relationship to HiringSelections
    public function selectedEmployees()
    {
        return $this->hasMany(HiringSelection::class, 'hiring_request_id');
    }

    // Define the relationship to HiringAssignments
    public function hiringAssignments()
    {
        return $this->hasMany(HiringAssignment::class, 'hiring_request_id')
                    ->where('status', 'Assigned'); // Only fetch assignments with status "Assigned"
    }


       // Custom relationship to released HiringAssignments
       public function releasedHiringAssignments()
       {
           return $this->hasMany(HiringAssignment::class, 'hiring_request_id')
                       ->where('status', 'released');  // Filters only released assignments
       }


        // Define the relationship to Payment
        public function payments()
        {
            return $this->hasMany(Payment::class, 'hiring_request_id');
        }
}
