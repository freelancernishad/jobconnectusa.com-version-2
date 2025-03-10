<?php

namespace App\Http\Controllers\Api\Admin\Users;

use App\Models\User;
use App\Models\Payment;
use App\Models\Profile;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    /**
     * Get all users who have made payments for activation.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUsersWithPendingPayments(Request $request)
    {
        // Get the search parameter from the request, if it exists
        $search = $request->input('search');

        // Initialize a search variable for use in the query
        $searchTerm = null;

        // Set search term if it exists
        if (!empty($search)) {
            $searchTerm = trim($search); // Clean the search input
        }

        // Query payments with pending status and type 'activation'
        $payments = Payment::where('type', 'activation')
                            ->where('status', 'pending')
                            ->whereHas('user', function ($query) use ($searchTerm) {
                                // Apply search filter if it exists, looking in both name and email
                                if (!empty($searchTerm)) {
                                    $query->where(function ($query) use ($searchTerm) {
                                        $query->where('name', 'like', '%' . $searchTerm . '%')
                                              ->orWhere('email', 'like', '%' . $searchTerm . '%');
                                    });
                                }
                            })
                            ->with('user') // Assuming the relationship is set up
                            ->orderBy('created_at', 'desc') // Sort by latest to oldest
                            ->get();

        // Extract user details from payments
        // $users = $payments->map(function ($payment) {
        //     return $payment; // Assuming 'user' is a relation on Payment model
        // });

        return response()->json([
            'success' => true,
            'message' => 'Users with pending payments retrieved successfully.',
            'data' => $payments, // Returning the users
        ]);
    }




    /**
     * Approve payment and activate the user.
     *
     * @param int $paymentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function approvePayment($paymentId)
    {
        $payment = Payment::find($paymentId);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'The specified payment could not be found. Please check the payment ID and try again.',
            ], 404);
        }

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'The payment is not currently in a pending status. Please check the payment status and try again.',
            ], 400);
        }

        // Update payment status to approved
        $payment->update(['status' => 'approved']);

        // Activate the user
        $user = User::find($payment->userid);
        if ($user) {
            $user->update([
                'status' => 'active',
                'step' => 3,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment has been approved, and the user has been activated successfully.',
        ]);

    }

    public function cancelPayment($paymentId)
    {
        // Find the payment by ID
        $payment = Payment::find($paymentId);

        // Check if the payment exists
        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'The specified payment could not be found. Please check the payment ID and try again.',
            ], 404);
        }

        // Check if the payment status is already canceled
        if ($payment->status === 'canceled') {
            return response()->json([
                'success' => false,
                'message' => 'The payment is already canceled.',
            ], 400);
        }

        // Check if the payment status is not pending or approved
        if ($payment->status !== 'pending' && $payment->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'The payment is not in a status that can be canceled. Please check the payment status and try again.',
            ], 400);
        }

        // Find the associated user
        $user = User::find($payment->userid);

        // If the user exists and their status is 'active'
        if ($user && $user->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'The payment cannot be canceled because the user is already active. Once a user is activated, canceling the payment is not allowed.',
            ], 400);
        }

        // Update payment status to canceled
        $payment->update(['status' => 'canceled']);

        // If the user exists and is not active, update the user's status
        if ($user) {
            $user->update([
                'status' => 'inactive',
                'activation_payment_made' => false,
                'activation_payment_cancel' => true,
                // 'step' => 1,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment has been canceled successfully.',
        ]);
    }



    public function getUsersByRole(Request $request)
    {
        // Validate the request parameters
        $request->validate([
            'search' => 'nullable|string', // Global search term (name, email, phone_number)
            'role' => 'nullable|in:EMPLOYEE,EMPLOYER', // Filter by profile_type
            'service' => 'nullable|integer', // Filter by preferred_job_title
            'per_page' => 'nullable|integer|min:1', // Pagination limit
        ]);

        // Get the search parameter for global search
        $searchQuery = $request->query('search');

        // Get the role parameter (optional)
        $role = $request->query('role'); // This corresponds to profile_type in the Profile model

        // Get the per_page parameter with a default of 10
        $perPage = $request->query('per_page', 10);

        // Start the query to retrieve users
        $query = User::query();

        // Apply global search filters if a search term is provided
        if ($searchQuery) {
            $query->where(function($q) use ($searchQuery) {
                $q->where('name', 'LIKE', '%' . $searchQuery . '%')
                  ->orWhere('email', 'LIKE', '%' . $searchQuery . '%')
                  ->orWhereHas('profile', function($profileQuery) use ($searchQuery) {
                      $profileQuery->where('phone_number', 'LIKE', '%' . $searchQuery . '%')
                                   ->orWhere('first_name', 'LIKE', '%' . $searchQuery . '%')
                                   ->orWhere('last_name', 'LIKE', '%' . $searchQuery . '%');
                  });
            });
        }

        // Apply role-based filters if a role is provided
        if ($role) {
            $query->whereHas('profile', function($profileQuery) use ($role) {
                $profileQuery->where('profile_type', $role);
            });
        }

        // Ensure only verified users are retrieved
        $query->whereNotNull('email_verified_at');

        // Get the service parameter for preferred job title filter (optional)
        $service = $request->query('service');

        // Apply preferred_job_title filter if service is provided
        if ($service) {
            $query->whereHas('profile', function($profileQuery) use ($service) {
                $profileQuery->where('preferred_job_title', $service);
            });
        }

        // Retrieve the users with eager loading and pagination
        $users = $query->with([
            'profile', // Load the profile relationship
            'languages',
            'certifications',
            'skills',
            'education',
            'employmentHistory',
            'resume',
            'thumbnail',
            'servicesLookingFor'
        ])->paginate($perPage);

         UserResource::collection($users);


        // Build response using jsonResponse
        return jsonResponse(true, 'Users retrieved successfully.', $users);
    }










    public function createUserAndProfile(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required_without:user_id|string|email|max:255|unique:users,email,' . $request->user_id,
            'password' => 'required|string|min:8',
            'profile_picture' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',

            // Profile fields
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'preferred_job_title' => 'nullable|string|max:255',
            'is_other_preferred_job_title' => 'nullable|boolean',
            'company_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'years_of_experience_in_the_industry' => 'nullable|integer',
            'preferred_work_state' => 'nullable|string|max:255',
            'preferred_work_zipcode' => 'nullable|string|max:255',
            'your_experience' => 'nullable|string',
            'familiar_with_safety_protocols' => 'nullable|boolean',
            'job_by' => 'nullable|string||in:INDIVIDUAL,COMPANY',
            'resume' => 'nullable|string',
            'status' => 'nullable|string|max:255',
            'profile_type' => 'nullable|string|in:EMPLOYEE,EMPLOYER',

            // Related models
            'languages' => 'nullable|array',
            'languages.*.language' => 'required_with:languages|string|max:255',
            'languages.*.level' => 'required_with:languages|string|max:255',

            'certifications' => 'nullable|array',
            'certifications.*.name' => 'required_with:certifications|string|max:255',
            'certifications.*.certified_from' => 'required_with:certifications|string|max:255',
            'certifications.*.year' => 'required_with:certifications|integer|digits:4',

            'skills' => 'nullable|array',
            'skills.*.name' => 'required_with:skills|string|max:255',
            'skills.*.level' => 'required_with:skills|string|max:255',

            'education' => 'nullable|array',
            'education.*.school_name' => 'required_with:education|string|max:255',
            'education.*.qualifications' => 'required_with:education|string|max:255',
            'education.*.start_date' => 'required_with:education|date',
            'education.*.end_date' => 'nullable|date|after_or_equal:education.*.start_date',
            'education.*.notes' => 'nullable|string',

            'employment_history' => 'nullable|array',
            'employment_history.*.company' => 'required_with:employment_history|string|max:255',
            'employment_history.*.position' => 'required_with:employment_history|string|max:255',
            'employment_history.*.start_date' => 'required_with:employment_history|date',
            'employment_history.*.end_date' => 'nullable|date|after_or_equal:employment_history.*.start_date',
            'employment_history.*.responsibilities' => 'nullable|string',

            'looking_services' => 'nullable|array',
            'looking_services.*' => 'required_with:looking_services|exists:services,id',

            'other_looking_services' => 'nullable|array',
            'other_looking_services.*' => 'required_with:other_looking_services|string|max:255',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // User creation or retrieval
        if ($request->filled('user_id')) {
            $user = User::findOrFail($request->user_id);
        } else {
            $user = User::create([
                'username' => $request->username ?? Str::before($request->email, '@'),
                'name' => trim(($request->first_name ?? '') . ' ' . ($request->last_name ?? '')),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_picture' => $request->profile_picture,
            ]);
        }

        // Profile creation or update
        $profile = Profile::updateOrCreate(
            [
                'user_id' => $user->id,
                'profile_type' => $request->profile_type
            ],
            [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'date_of_birth' => $request->date_of_birth,
                'profile_picture' => $request->profile_picture,
                'preferred_job_title' => $request->preferred_job_title,
                'is_other_preferred_job_title' => $request->is_other_preferred_job_title,
                'company_name' => $request->company_name,
                'description' => $request->description,
                'years_of_experience_in_the_industry' => $request->years_of_experience_in_the_industry,
                'preferred_work_state' => $request->preferred_work_state,
                'preferred_work_zipcode' => $request->preferred_work_zipcode,
                'your_experience' => $request->your_experience,
                'familiar_with_safety_protocols' => $request->familiar_with_safety_protocols,
                'job_by' => $request->job_by,
                'activation_payment_made' => 0,
                'activation_payment_cancel' => 0,
                'resume' => $request->resume,
                'status' => $request->status ?? ($request->profile_type === 'EMPLOYER' ? 'active' : 'inactive'),
                'step' => 2,
            ]
        );

        // Manage related models
        createRelatedModels($user, $request);

        // Load relations
        $user->load([
            'languages',
            'certifications',
            'skills',
            'education',
            'employmentHistory',
            'lookingServices',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User and profile processed successfully',
            'user' => new UserResource($user),
        ], 201);
    }





}
