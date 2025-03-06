<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringSelection extends Model
{
    use HasFactory;

    protected $fillable = [
        'hiring_request_id',
        'employee_id',
        'selection_note',
    ];

    // Relationship with HiringRequest
    public function hiringRequest()
    {
        return $this->belongsTo(HiringRequest::class);
    }

    // Relationship with Employee (User)
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'employee_id'); // Adjust if needed
    }
}
