<?php

namespace App\Http\Controllers\Api\Admin\Hiring;

use Illuminate\Http\Request;
use App\Models\HiringRequest;
use App\Models\HiringSelection;
use App\Models\HiringAssignment;
use App\Models\EmployeeHiringPrice;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin; // Ensure this model exists
use App\Models\User; // Assuming Employee is also a User

class HiringProcessController extends Controller
{
    /**
     * Create a new hiring request.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createHiringRequest(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'employer_id' => 'required|exists:users,id',
            'job_title' => 'required|string|max:255',
            'job_description' => 'required|string',
            'expected_start_date' => 'required|date',
            'salary_offer' => 'required|numeric',
            'selected_employees' => 'required|array',
            'selected_employees.*' => 'required|exists:users,id',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
            'employee_needed' => 'required|integer|min:1',  // Validate employee_needed

        ]);

        // Create the hiring request
        $hiringRequest = HiringRequest::create([
            'employer_id' => $request->input('employer_id'),
            'job_title' => $request->input('job_title'),
            'job_description' => $request->input('job_description'),
            'expected_start_date' => $request->input('expected_start_date'),
            'salary_offer' => $request->input('salary_offer'),
            'employee_needed' => $request->input('employee_needed'),  // Store employee_needed
            'status' => 'Prepaid',
        ]);

        // Attach selected employees to the hiring request
        foreach ($request->input('selected_employees') as $employeeId) {
            HiringSelection::create([
                'hiring_request_id' => $hiringRequest->id,
                'employee_id' => $employeeId,
                'selection_note' => null, // Optionally set a default note
            ]);
        }

        // Get the total number of selected employees
        $totalEmployees = count($request->input('selected_employees'));

        // Retrieve the hiring price based on the number of employees
        $employeeHiringPrice = EmployeeHiringPrice::where('min_number_of_employees', '<=', $totalEmployees)
            ->where('max_number_of_employees', '>=', $totalEmployees)
            ->first();

        if (!$employeeHiringPrice) {
            return response()->json([
                'success' => false,
                'message' => 'No pricing available for the selected number of employees.',
            ], 400);
        }

        // Calculate total cost based on the range and price per employee
        $totalHiringCost = $employeeHiringPrice->calculateTotalPrice($request->employee_needed);

        // Payment data
        $paymentData = [
            'name' => $request->input('job_title'),
            'userid' => $request->input('employer_id'),
            'amount' => $totalHiringCost,
            'applicant_mobile' => '1234567890', // This should come from employer's data
            'success_url' => $request->success_url, // Assuming no balance is used here
            'cancel_url' => $request->cancel_url, // Assuming no balance is used here
            'hiring_request_id' => $hiringRequest->id, // Send the hiring_request_id
            'type' => "Hiring-Request"
        ];

        // Trigger the Stripe payment and get the redirect URL
        $paymentUrl = stripe($paymentData);

        return response()->json([
            'success' => true,
            'message' => 'Hiring request created successfully. You will be redirected for payment.',
            'hiring_request' => $hiringRequest,
            'paymentUrl' => $paymentUrl['session_url'],
        ], 201);
    }


    /**
     * Assign an employee to a hiring request.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignEmployee(Request $request, $id)
{


    $validator = Validator::make($request->all(), [
        'assigned_employee_id' => 'required|array',
        'assigned_employee_id.*' => 'exists:users,id',
        'assignment_note' => 'nullable|string',
        'assignment_date' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }



    $hiringRequest = HiringRequest::findOrFail($id);

    // Count the current assignments
    $currentAssignmentsCount = $hiringRequest->hiringAssignments()->count();

    // Check if the new assignments match the employee_needed exactly
    $newAssignmentsCount = count($request->input('assigned_employee_id'));
    if ($currentAssignmentsCount + $newAssignmentsCount != $hiringRequest->employee_needed) {
        return response()->json([
            'success' => false,
            'message' => 'The total number of assigned employees must equal the required number of employees (' . $hiringRequest->employee_needed . ').',
        ], 400);
    }

    // Assign each employee from the provided array
    foreach ($request->input('assigned_employee_id') as $employeeId) {
        HiringAssignment::create([
            'hiring_request_id' => $id,
            'assigned_employee_id' => $employeeId,
            'admin_id' => $request->user()->id, // Assuming admin is the current authenticated user
            'assignment_note' => $request->input('assignment_note'),
            'assignment_date' => $request->input('assignment_date'),
            'status' => 'Assigned',
        ]);
    }

    // Update the status of the hiring request to 'Assigned'
    $hiringRequest->update(['status' => 'Assigned']);

    return response()->json([
        'success' => true,
        'message' => 'Employees successfully assigned to the hiring request. The assignments have been recorded.',
    ], 200);
}


    public function releaseEmployee($id)
    {
        // Find the HiringAssignment by its id
        $assignment = HiringAssignment::findOrFail($id);

        // Release the employee from the assignment
        $assignment->releaseEmployee();


        return response()->json([
            'success' => true,
            'message' => 'Employee has been released from the assignment.',
        ], 200);
    }

    /**
     * Get details of a hiring request including assigned employee.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHiringRequest($id)
    {
        $hiringRequest = HiringRequest::with(['selectedEmployees.employee', 'hiringAssignments.employee'])
                                      ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Hiring request details retrieved successfully.',
            'hiring_request' => [
                'id' => $hiringRequest->id,
                'employer_id' => $hiringRequest->employer_id,
                'job_title' => $hiringRequest->job_title,
                'job_description' => $hiringRequest->job_description,
                'expected_start_date' => $hiringRequest->expected_start_date,
                'salary_offer' => $hiringRequest->salary_offer,
                'status' => $hiringRequest->status,
                'selected_employees' => $hiringRequest->selectedEmployees->map(function ($selection) {
                    return $selection->employee;
                }),
                'assigned_employee' => $hiringRequest->hiringAssignments->first()?->employee ?: null,
            ],
        ], 200);
    }

    /**
     * Get all hiring requests by a specific step.
     *
     * @param string $step
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequestsByStep($step)
    {
        $requests = HiringRequest::where('status', $step)->get();

        return response()->json([
            'success' => true,
            'message' => "Hiring requests at the '$step' step retrieved successfully.",
            'requests' => $requests,
        ], 200);
    }

    /**
     * Get all hiring requests (for admin) with pagination or filtering if needed.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllRequests()
    {
        $requests = HiringRequest::all(); // or use paginate() for pagination

        return response()->json([
            'success' => true,
            'message' => 'All hiring requests retrieved successfully.',
            'requests' => $requests,
        ], 200);
    }

    /**
     * Get all hiring requests for a specific employer.
     *
     * @param int $employerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequestsByEmployer($employerId)
    {
        $requests = HiringRequest::where('employer_id', $employerId)->get();

        return response()->json([
            'success' => true,
            'message' => "Hiring requests for employer ID $employerId retrieved successfully.",
            'employer_id' => $employerId,
            'requests' => $requests,
        ], 200);
    }

    /**
     * Get all hiring requests by their step with pagination.
     *
     * @param string $step
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequestsByStepWithPagination(Request $request,$step)
    {

        $perPage = $request->query('per_page', 10);

        $requests = HiringRequest::with([
            'employer.servicesLookingFor',

            'selectedEmployees.employee.languages',
            'selectedEmployees.employee.certifications',
            'selectedEmployees.employee.skills',
            'selectedEmployees.employee.education',
            'selectedEmployees.employee.employmentHistory',
            'selectedEmployees.employee.resume',


            'hiringAssignments.employee.languages',
            'hiringAssignments.employee.certifications',
            'hiringAssignments.employee.skills',
            'hiringAssignments.employee.education',
            'hiringAssignments.employee.employmentHistory',
            'hiringAssignments.employee.resume',


            'releasedHiringAssignments.employee.languages',
            'releasedHiringAssignments.employee.certifications',
            'releasedHiringAssignments.employee.skills',
            'releasedHiringAssignments.employee.education',
            'releasedHiringAssignments.employee.employmentHistory',
            'releasedHiringAssignments.employee.resume',

        ])->where('status', $step)->paginate($perPage); // Adjust pagination as needed

        return response()->json([
            'success' => true,
            'message' => "Hiring requests at the '$step' step retrieved successfully with pagination.",
            'step' => $step,
            'requests' => $requests,
        ], 200);
    }
}
