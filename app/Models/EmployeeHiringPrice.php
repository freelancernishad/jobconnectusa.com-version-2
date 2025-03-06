<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeHiringPrice extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'min_number_of_employees',
        'max_number_of_employees',
        'price_per_employee',
        'total_price'
    ];

      /**
     * Automatically calculate the total price based on the number of employees.
     *
     * @param int $numberOfEmployees
     * @return float
     */
    public function calculateTotalPrice($numberOfEmployees)
    {


        // Retrieve the hiring price where the number of employees is within the range
        $hiringPrice = EmployeeHiringPrice::where('min_number_of_employees', '<=', $numberOfEmployees)
                                        ->where('max_number_of_employees', '>=', $numberOfEmployees)
                                        ->first();

        if ($hiringPrice) {
          return  $totalPrice = $hiringPrice->price_per_employee * $numberOfEmployees;

        } else {
           return 1;
        }
    }

    /**
     * Set the total price attribute automatically whenever the model is saved.
     *
     * @param  float  $value
     * @return void
     */
    public function setTotalPriceAttribute($value)
    {
        // Assuming you're passing the actual number of employees
        $this->attributes['total_price'] = $this->calculateTotalPrice($value);
    }
}
