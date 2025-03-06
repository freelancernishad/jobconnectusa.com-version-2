<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrowsingHistory extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'viewed_user_id', 'viewed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function viewedUser()
    {
        return $this->belongsTo(User::class, 'viewed_user_id');
    }
}
