<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon'];

    public function skillLists()
    {
        return $this->hasMany(SkillList::class);
    }

    public function usersLookingFor()
    {
        return $this->belongsToMany(User::class, 'user_looking_services');
    }

}
