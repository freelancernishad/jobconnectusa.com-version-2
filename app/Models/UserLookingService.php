<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserLookingService extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'service_id','service_title'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }


    // public function service()
    // {
    //     if (is_null($this->service_id)) {
    //         return (object) [
    //             'name' => $this->service_title,
    //             'icon' => ''
    //         ];
    //     }

    //     return $this->belongsTo(Service::class);
    // }

}
