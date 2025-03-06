<?php

namespace App\Http\Controllers\Api\Admin\Hiring;

use App\Http\Controllers\Controller;
use App\Models\EmployeeHiringPrice;
use Illuminate\Http\Request;

class EmployeeHiringPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Fetch all pricing rules
        $prices = EmployeeHiringPrice::orderBy('min_number_of_employees', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Employee hiring prices retrieved successfully.',
            'data' => $prices,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'min_number_of_employees' => 'required|integer|min:1',
            'max_number_of_employees' => 'required|integer|gte:min_number_of_employees',
            'price_per_employee' => 'required|numeric|min:0',
        ]);

        // Check if there's any overlapping range in the database
        $rangeExists = EmployeeHiringPrice::where(function ($query) use ($validated) {
            $query->whereBetween('min_number_of_employees', [$validated['min_number_of_employees'], $validated['max_number_of_employees']])
                  ->orWhereBetween('max_number_of_employees', [$validated['min_number_of_employees'], $validated['max_number_of_employees']]);
        })->exists();

        if ($rangeExists) {
            return response()->json([
                'success' => false,
                'message' => 'The employee range overlaps with an existing record.',
                'data' => null,
            ], 422); // 422 = Unprocessable Entity
        }

        // Calculate total price based on the range of employees
        $totalEmployees = ($validated['max_number_of_employees'] - $validated['min_number_of_employees'] + 1);
        $totalPrice = $totalEmployees * $validated['price_per_employee'];

        // Create a new price rule
        $price = EmployeeHiringPrice::create([
            'min_number_of_employees' => $validated['min_number_of_employees'],
            'max_number_of_employees' => $validated['max_number_of_employees'],
            'price_per_employee' => $validated['price_per_employee'],
            'total_price' => $totalPrice,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee hiring price created successfully.',
            'data' => $price,
        ], 201); // 201 = Created
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\EmployeeHiringPrice  $employeeHiringPrice
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(EmployeeHiringPrice $employeeHiringPrice)
    {
        return response()->json([
            'success' => true,
            'message' => 'Employee hiring price retrieved successfully.',
            'data' => $employeeHiringPrice,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmployeeHiringPrice  $employeeHiringPrice
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, EmployeeHiringPrice $employeeHiringPrice)
    {
        // Validate input
        $validated = $request->validate([
            'min_number_of_employees' => 'required|integer|min:1',
            'max_number_of_employees' => 'required|integer|gte:min_number_of_employees',
            'price_per_employee' => 'required|numeric|min:0',
        ]);

        // Check for overlapping range, but exclude the current record from the query
        $rangeExists = EmployeeHiringPrice::where('id', '!=', $employeeHiringPrice->id)
            ->where(function ($query) use ($validated) {
                $query->whereBetween('min_number_of_employees', [$validated['min_number_of_employees'], $validated['max_number_of_employees']])
                      ->orWhereBetween('max_number_of_employees', [$validated['min_number_of_employees'], $validated['max_number_of_employees']]);
            })
            ->exists();

        if ($rangeExists) {
            return response()->json([
                'success' => false,
                'message' => 'The employee range overlaps with an existing record.',
                'data' => null,
            ], 422); // 422 = Unprocessable Entity
        }

        // Calculate total price based on the range of employees
        $totalEmployees = ($validated['max_number_of_employees'] - $validated['min_number_of_employees'] + 1);
        $totalPrice = $totalEmployees * $validated['price_per_employee'];

        // Update the price rule
        $employeeHiringPrice->update([
            'min_number_of_employees' => $validated['min_number_of_employees'],
            'max_number_of_employees' => $validated['max_number_of_employees'],
            'price_per_employee' => $validated['price_per_employee'],
            'total_price' => $totalPrice,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Employee hiring price updated successfully.',
            'data' => $employeeHiringPrice,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\EmployeeHiringPrice  $employeeHiringPrice
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(EmployeeHiringPrice $employeeHiringPrice)
    {
        $employeeHiringPrice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee hiring price deleted successfully.',
            'data' => null,
        ], 204); // 204 = No Content
    }
}
