<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'user_id',
        'created_by',
        'company_name',
        'location',
        'position',
        'employment_type',
        'hourly_rate_min',
        'hourly_rate_max',
        'website',
        'company_logo',
        'total_positions',
        'positions_filled',
        'status',
    ];

    /**
     * Accessor for calculating remaining positions.
     *
     * @return int
     */
    public function getPositionsRemainingAttribute()
    {
        return $this->total_positions - $this->positions_filled;
    }

    /**
     * Relationship with the Admin model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Relationship with the User model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
