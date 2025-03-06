<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'school_name',
        'qualifications',
        'start_date',
        'end_date',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
