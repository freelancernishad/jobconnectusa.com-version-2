<?php

namespace App\Http\Controllers\Api\User\UserManagement;

use Carbon\Carbon;

use App\Models\User;
use App\Models\Payment;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\ProfileResource;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{



    public function updateAllUserNames()
    {
        // Get all users from the database
        $users = User::all();

        // Loop through each user
        foreach ($users as $user) {
            // Combine first_name and last_name
            $name = $user->first_name . ' ' . $user->last_name;

            // Update the name field
            $user->name = $name;

            // Save the updated user
            $user->save();
        }

        return jsonResponse(true, 'All user names updated successfully!');
    }



    public function registerStep2(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Validate user existence and email verification
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'The user you are trying to access does not exist.',
            ], 404);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Your email address is not verified. Please verify your email before proceeding.',
            ], 403);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'profile_picture' => 'nullable|string|max:255',
            'preferred_job_title' => 'nullable|string|max:255',
            'is_other_preferred_job_title' => 'nullable|boolean',
            'company_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'years_of_experience_in_the_industry' => 'nullable|string',
            'preferred_work_state' => 'nullable|string|max:255',
            'preferred_work_zipcode' => 'nullable|string|max:10',
            'your_experience' => 'nullable|string',
            'familiar_with_safety_protocols' => 'nullable|boolean',
            // 'resume' => 'nullable|file|mimes:pdf,doc,docx|max:10240',

            // Validation for related models
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

            // Validation for UserLookingService
            'looking_services' => 'nullable|array',
            'looking_services.*' => 'required|exists:services,id',

            'other_looking_services' => 'nullable|array',
            'other_looking_services.*' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Prepare profile data
        $profileData = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth ? date("Y-m-d", strtotime($request->date_of_birth)) : null,
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
            'step' => 2, // Set step value to 2
        ];

        // Handle resume upload
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes', 'protected');
            $profileData['resume'] = $resumePath;
        }
        $active_profile = $user->active_profile;

        if($active_profile=='EMPLOYER'){
            $profileData['status'] = 'active';
        }


        if ($user->active_profile_id === null) {
            // If active_profile_id is null, create a new profile
            $profileData['profile_type'] = $active_profile;
            $profile = $user->profile()->create($profileData);
            $user->active_profile_id = $profile->id;
            $user->save();
        } else {
            // If active_profile_id is not null, check if the profile exists
            $profile = Profile::find($user->active_profile_id);

            if (!$profile) {
                // If the profile doesn't exist, create a new one
                $profileData['profile_type'] = $active_profile;
                $profile = $user->profile()->create($profileData);
                $user->active_profile_id = $profile->id;
                $user->save();
            } else {
                // If the profile exists, update it
                $profile->update($profileData);
            }
        }




        // Update related models
        $this->updateRelatedModels($user, $request);
       $profile = new ProfileResource($profile);

        return  response()->json($profile);
    }

    /**
     * Update related models for the profile.
     *
     * @param Profile $profile
     * @param Request $request
     */
    private function updateRelatedModels(User $user, Request $request)
    {
        // Update languages
        if ($request->has('languages')) {
            $user->languages()->delete();
            $user->languages()->createMany($request->languages);
        }

        // Update certifications
        if ($request->has('certifications')) {
            $user->certifications()->delete();
            $user->certifications()->createMany($request->certifications);
        }

        // Update skills
        if ($request->has('skills')) {
            $user->skills()->delete();
            $user->skills()->createMany($request->skills);
        }

        // Update education
        if ($request->has('education')) {
            $user->education()->delete();
            $user->education()->createMany($request->education);
        }

        // Update employment history
        if ($request->has('employment_history')) {
            $user->employmentHistory()->delete();
            $user->employmentHistory()->createMany($request->employment_history);
        }

        // Update looking services
        if ($request->has('looking_services') || $request->has('other_looking_services')) {
            $user->lookingServices()->delete();

            if ($request->has('looking_services')) {
                $user->lookingServices()->createMany(array_map(fn($id) => ['service_id' => $id], $request->looking_services));
            }

            if ($request->has('other_looking_services')) {
                $user->lookingServices()->createMany(array_map(fn($title) => ['service_title' => $title], $request->other_looking_services));
            }
        }
    }


    public function registerStep3(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Check if user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User is not authenticated. Please log in and try again.',
            ], 401);
        }

        // Check if the user has an active profile
        if ($user->active_profile_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'No active profile found. Please complete Step 2 first.',
            ], 400);
        }

        // Retrieve the active profile
        $profile = Profile::find($user->active_profile_id);


        // Check if the profile exists
        if (!$profile) {
            return response()->json([
                'success' => false,
                'message' => 'Active profile not found. Please complete Step 2 first.',
            ], 400);
        }

        // Check if user status is inactive
        if ($profile->status !== 'inactive') {
            return response()->json([
                'success' => false,
                'message' => 'The user is already active and does not need to complete this step again.',
            ], 400);
        }

        // Check user step
        if ($profile->step === 1) {
            return response()->json([
                'success' => false,
                'message' => 'Please complete Step 2 before proceeding to the payment process.',
            ], 400);
        }

        if ($profile->step !== 2) {
            return response()->json([
                'success' => false,
                'message' => 'The user is in an unexpected state. Please contact support for assistance.',
            ], 400);
        }

        // Check if payment has already been made
        if ($profile->activation_payment_made) {
            return response()->json([
                'success' => false,
                'message' => 'Activation payment has already been processed for this user. Please contact the admin for further assistance.',
            ], 400);
        }

        // Validate the payment method
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|string|in:card,cash', // Only allow 'card' or 'cash'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Retrieve the payment method from the request
        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod === 'card') {
            // Payment data for card
            $paymentData = [
                'name' => $user->name,
                'userid' => $user->id,
                'amount' => 100, // Assuming fixed payment amount for this case
                'applicant_mobile' => '1234567890', // This should come from employer's data
                'success_url' => $request->input('success_url'),
                'cancel_url' => $request->input('cancel_url'),
                'type' => "activation"
            ];

            // Trigger the Stripe payment and get the redirect URL
            try {
                $paymentUrl = stripe($paymentData);
                $profile->update(['activation_payment_made' => false, 'activation_payment_cancel' => false]);
                return response()->json([
                    'success' => true,
                    'message' => 'Redirect to payment',
                    'payment_url' => $paymentUrl['session_url'],
                    'payment' => $paymentUrl['payment']
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the card payment: ' . $e->getMessage(),
                ], 500);
            }
        } else if ($paymentMethod === 'cash') {
            // Execute existing code for cash payments
            try {
                $paymentResponse = createPayment(100);

                // Ensure paymentResponse is an array and contains 'success' key
                if (is_array($paymentResponse) && isset($paymentResponse['success'])) {
                    if ($paymentResponse['success']) {
                        // Update profile to indicate that payment has been made
                        $profile->update(['activation_payment_made' => true, 'activation_payment_cancel' => false]);
                        return response()->json($paymentResponse);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => $paymentResponse['message'] ?? 'Payment creation failed. Please try again later or contact support if the problem persists.',
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid payment response format. Please try again later or contact support if the problem persists.',
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the payment: ' . $e->getMessage(),
                ], 500);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method selected. Please choose either "card" or "cash".',
            ], 400);
        }
    }






    // User delete
    public function delete($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'The requested user could not be found. Please verify the user ID and try again.',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'The user has been successfully deleted.',
        ], 200);
    }

    // Show user details

   // app/Http/Controllers/Global/YourController.php

   public function getUserByUsername(string $username)
   {
       $user = User::where('username', $username)
                   ->with([
                       'languages',
                       'certifications',
                       'skills',
                       'education',
                       'employmentHistory',
                       'resume',
                        'userLookingServices',
                       'thumbnail'
                   ])
                   ->first();


       if (!$user) {
           return response()->json([
               'success' => false,
               'message' => 'User not found.',
           ], 404);
       }

       // Get the ID of the currently logged-in user
       $currentUserId = auth()->id();

       // Add the total number of likes received to the user object
       $user->services_looking_for = $user->allServicesLookingFor();
       $user->total_likes_received = $user->receivedLikes()->count();


       // Check if the currently authenticated user has liked this user and add it to the user object
       $user->user_liked_by_current_user = $user->isLikedByUser($currentUserId);

       // Add pending hiring and hired employees
       $user->pending_hiring = $user->pendingHiring(); // Get pending hiring
       $user->hired_employees = $user->hiredEmployees(); // Get hired employees
       $user->got_hired = $user->got_hired(); // the list of jobs

       // Add email verification status
       $user->email_verified = $user->email_verified_at ? true : false;

       // Log browsing history
       logBrowsingHistory($user->id);

       return response()->json([
           'success' => true,
           'message' => 'User retrieved successfully.',
           'data' => new UserResource($user),  // All attributes are now part of the $user object
       ], 200);
   }





   public function getEmployeesYouMayLike(Request $request)
   {
       // Get the logged-in user (employer)
       $user = auth()->user();

       // Ensure the user is an employer
       if (!$user || $user->role !== 'EMPLOYER') {
           return response()->json([
               'success' => false,
               'message' => 'User must be an employer.',
               'data' => null,
           ], 403);
       }

       // Get the employer's preferred job titles (services they're looking for)
       $lookingServiceIds = $user->lookingServices->pluck('service_id')->toArray(); // Extract service_id values

       // Check if the employer has valid preferred services
       if (empty($lookingServiceIds)) {
           return response()->json([
               'success' => false,
               'message' => 'No valid preferred services found for the employer.',
               'data' => null,
           ], 404);
       }

       // Get per_page from request, if not provided default to 10
       $perPage = $request->get('per_page', 10);

       // Find all employees whose preferred_job_title matches any of the employer's looking services and are active
       $employees = User::where('role', 'EMPLOYEE')
           ->whereIn('preferred_job_title', $lookingServiceIds) // Handle multiple services
           ->where('status', 'active') // Only active employees
           ->where('id', '!=', $user->id) // Exclude the logged-in user (employer)
           ->with('thumbnail') // Include thumbnail relationship if exists
           ->paginate($perPage); // Apply pagination with per_page value

    //    // Check if no employees were found
    //    if ($employees->isEmpty()) {
    //        // Return random active users if no employees match the criteria
    //        return response()->json([
    //            'success' => true,
    //            'message' => 'No matching employees found, returning random active users.',
    //            'data' => getRandomActiveUsers(),
    //        ], 200);
    //    }

       return response()->json([
           'success' => true,
           'message' => 'Employees retrieved successfully.',
           'data' => $employees,
       ], 200);
   }









public function updateProfileByToken(Request $request)
{
    // Get the authenticated user via JWT
    $user = auth()->user();

    // Check if the user exists
    if (!$user) {
        return jsonResponse(false, 'The user you are trying to access does not exist. Please check the user ID and try again.', null, 404);
    }

    // Check if the user's email is verified
    if (!$user->hasVerifiedEmail()) {
        return jsonResponse(false, 'Your email address is not verified. Please verify your email before proceeding.', null, 403);
    }

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'first_name' => 'nullable|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'phone_number' => [
            'nullable',
            'string',
            'max:15'
        ],
        'address' => 'nullable|string|max:255',
        'date_of_birth' => 'nullable|date',
        'profile_picture' => 'nullable|string|max:255',
        'preferred_job_title' => 'nullable|string|max:255',
        'is_other_preferred_job_title' => 'nullable',
        'company_name' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'years_of_experience_in_the_industry' => 'nullable|string',
        'preferred_work_state' => 'nullable|string|max:255',
        'preferred_work_zipcode' => 'nullable|string|max:10',
        'your_experience' => 'nullable|string',
        'familiar_with_safety_protocols' => 'nullable|boolean',

        // Validation for related models
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

        // Validation for UserLookingService
        'looking_services' => 'nullable|array',
        'looking_services.*' => 'required|exists:services,id',

        'other_looking_services' => 'nullable|array',
        'other_looking_services.*' => 'required|string|max:255'


    ]);

    if ($validator->fails()) {
        return jsonResponse(false, 'Validation errors occurred.', null, 400, ['errors' => $validator->errors()]);
    }


    // Combine first_name and last_name dynamically
    $firstName = $request->has('first_name') ? $request->first_name : $user->first_name;
    $lastName = $request->has('last_name') ? $request->last_name : $user->last_name;
    $name = $firstName . ' ' . $lastName;

    // Update the user's name
    $user->name = $name;

    // Update the user's fields
    if ($request->has('first_name')) {
        $user->first_name = $request->first_name;
    }
    if ($request->has('last_name')) {
        $user->last_name = $request->last_name;
    }
    if ($request->has('phone_number')) {
        $user->phone_number = $request->phone_number;
    }
    if ($request->has('address')) {
        $user->address = $request->address;
    }
    if ($request->has('date_of_birth')) {
        $user->date_of_birth = $request->date_of_birth;
    }
    if ($request->has('profile_picture')) {
        $user->profile_picture = $request->profile_picture;
    }
    if ($request->has('preferred_job_title')) {
        $user->preferred_job_title = $request->preferred_job_title;
    }
    if ($request->has('is_other_preferred_job_title')) {
        $user->is_other_preferred_job_title = $request->is_other_preferred_job_title;
    }
    if ($request->has('company_name')) {
        $user->company_name = $request->company_name;
    }
    if ($request->has('description')) {
        $user->description = $request->description;
    }
    if ($request->has('years_of_experience_in_the_industry')) {
        $user->years_of_experience_in_the_industry = $request->years_of_experience_in_the_industry;
    }
    if ($request->has('preferred_work_state')) {
        $user->preferred_work_state = $request->preferred_work_state;
    }
    if ($request->has('preferred_work_zipcode')) {
        $user->preferred_work_zipcode = $request->preferred_work_zipcode;
    }
    if ($request->has('your_experience')) {
        $user->your_experience = $request->your_experience;
    }
    if ($request->has('familiar_with_safety_protocols')) {
        $user->familiar_with_safety_protocols = $request->familiar_with_safety_protocols;
    }

    // Handle resume upload
    if ($request->hasFile('resume')) {
        $resumePath = $request->file('resume')->store('resumes', 'protected'); // Store resume in protected storage
        $user->resume = $resumePath;
    }

    // Save the user
    $user->save();

       // Update related models: Delete existing data, then recreate
       if ($request->has('languages')) {
        $user->languages()->delete(); // Delete existing languages
        foreach ($request->languages as $languageData) {
            $user->languages()->create([
                'language' => $languageData['language'],
                'level' => $languageData['level'],
            ]);
        }
    }

    if ($request->has('certifications')) {
        $user->certifications()->delete(); // Delete existing certifications
        foreach ($request->certifications as $certificationData) {
            $user->certifications()->create([
                'name' => $certificationData['name'],
                'certified_from' => $certificationData['certified_from'],
                'year' => $certificationData['year'],
            ]);
        }
    }

    if ($request->has('skills')) {
        $user->skills()->delete(); // Delete existing skills
        foreach ($request->skills as $skillData) {
            $user->skills()->create([
                'name' => $skillData['name'],
                'level' => $skillData['level'],
            ]);
        }
    }

    if ($request->has('education')) {
        $user->education()->delete(); // Delete existing education
        foreach ($request->education as $educationData) {
            $user->education()->create([
                'school_name' => $educationData['school_name'],
                'qualifications' => $educationData['qualifications'],
                'start_date' => date("Y-m-d", strtotime($educationData['start_date'])),
                'end_date' => date("Y-m-d", strtotime($educationData['end_date'])),
                'notes' => $educationData['notes'],
            ]);
        }
    }

    if ($request->has('employment_history')) {
        $user->employmentHistory()->delete(); // Delete existing employment history
        foreach ($request->employment_history as $employmentData) {
            $user->employmentHistory()->create([
                'company' => $employmentData['company'],
                'position' => $employmentData['position'],
                'start_date' => date("Y-m-d", strtotime($employmentData['start_date'])),
                'end_date' => date("Y-m-d", strtotime($employmentData['end_date'])),
                'primary_responsibilities' => $employmentData['responsibilities'],
            ]);
        }
    }

       // Update looking services
       if ($request->has('looking_services') || $request->has('other_looking_services')) {
        // Delete existing looking services for this user
        $user->lookingServices()->delete();

        // Handle `looking_services` with `service_id`
        if ($request->has('looking_services')) {
            foreach ($request->looking_services as $serviceId) {
                $user->lookingServices()->create([
                    'service_id' => $serviceId,
                ]);
            }
        }

        // Handle `other_looking_services` with `service_title`
        if ($request->has('other_looking_services')) {
            foreach ($request->other_looking_services as $serviceTitle) {
                $user->lookingServices()->create([
                    'service_title' => $serviceTitle,
                ]);
            }
        }
    }

    return jsonResponse(true, 'Profile updated successfully!', $user);
}




public function cancelPaymentByUserId(Request $request)
{
    // Get the authenticated user from the JWT token
    $userId = auth()->user()->id;  // Assuming 'auth' is using JWT authentication

    // Find the activation payment(s) for the user
    $payments = Payment::where('userid', $userId)
                    ->where('type', 'activation')
                    ->where('status', 'pending')
                    ->get(); // Retrieve payments as a collection

    // Check if there are no pending activation payments
    if ($payments->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No pending activation payment found for this user.',
        ], 404);
    }

    // Iterate over each payment to check its status
    foreach ($payments as $payment) {
        // Check if the payment is already canceled
        if ($payment->status === 'canceled') {
            return response()->json([
                'success' => false,
                'message' => 'One of the payments has already been canceled.',
            ], 400);
        }

        // Find the associated user
        $user = User::find($userId);

        // If the user exists and their status is 'active'
        if ($user && $user->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'The payment cannot be canceled because the user is already active.',
            ], 400);
        }

        // Update the payment status to canceled
        $payment->update(['status' => 'canceled']);

        // If the user exists and is not active, update the user's status
        if ($user) {
            $user->update([
                'status' => 'inactive',
                'activation_payment_made' => false,
                'activation_payment_cancel' => true,
            ]);
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Activation payment(s) have been canceled successfully.',
    ]);
}






}
