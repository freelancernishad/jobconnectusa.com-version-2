<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'hiring_request_id',
        'assigned_employee_id',
        'admin_id',
        'assignment_note',
        'assignment_date',
        'status',
    ];

    // Relationship with Admin
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // Define the relationship to HiringRequest
    public function hiringRequest()
    {
        return $this->belongsTo(HiringRequest::class);
    }

    // Define the relationship to User (for assigned employee)
    public function employee()
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

        // Method to release the employee (end the assignment)
        public function releaseEmployee()
        {
            $this->status = 'released';  // or 'ended' or whatever your release status is
            $this->save();
        }
}
