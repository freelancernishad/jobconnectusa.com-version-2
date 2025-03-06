<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'liked_user_id'];

    // Define the relationship to the liked user
    public function likedUser()
    {
        return $this->belongsTo(User::class, 'liked_user_id');
    }

    // Define the relationship to the user who liked
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
