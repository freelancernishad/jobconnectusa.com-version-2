<?php

use Carbon\Carbon;
use App\Models\User;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\TokenBlacklist;
use App\Models\BrowsingHistory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

function TokenBlacklist($token){
// Get the authenticated user for each guard
    $user = null;
    $guardType = null;

    if (Auth::guard('admin')->check()) {
        $user = Auth::guard('admin')->user();
        $guardType = 'admin';
    } elseif (Auth::guard('user')->check()) {
        $user = Auth::guard('user')->user();
        $guardType = 'user';
    }


    TokenBlacklist::create([
            'token' => $token,
            'user_id' => $user->id,
            'user_type' => $guardType,
            'date' => Carbon::now(),
            ]);
}



function validateRequest(array $data, array $rules)
{
    $validator = Validator::make($data, $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    return null; // Return null if validation passes
}


function jsonResponse($success, $message, $data = null, $statusCode = 200, array $extraFields = [])
{
    // Build the base response structure
    // $response = [
    //     'success' => $success,
    //     'message' => $message,
    //     'data' => $data
    // ];

    $response = $data;

    // Merge any extra fields into the response
    if (!empty($extraFields)) {
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];
        $response = array_merge($response, $extraFields);
    }

    // Return the JSON response with the given status code
    return response()->json($response, $statusCode);
}


function otherPreferredJobTitle()
{
    $jobTitles = User::where('is_other_preferred_job_title', true)
        ->select('preferred_job_title as name') // Select relevant columns and rename 'preferred_job_title' to 'name'
        ->get();

    // Format the response
    return response()->json([
        'success' => true,
        'message' => 'Successfully retrieved the list of preferred job titles.',
        'data' => $jobTitles
    ]);
}


function createPayment($amount=1,$payment_method='cash',$type='activation')
{

    $user = Auth::user();

    if($payment_method=='cash'){
        $method ='cash';
    }else{
        $method ='card';

    }

        // Create a payment record
        $payment = Payment::create([
            'union' => 'initial', // Assuming user has a 'union' attribute
            'trxId' => generateTransactionId(), // Implement this method to generate unique transaction IDs
            'userid' => $user->id,
            'type' => $type, // Set type from request
            'amount' => $amount,
            'applicant_mobile' => $user->phone_number,
            'status' => 'pending',
            'date' => now()->format('Y-m-d'),
            'month' => now()->format('m'),
            'year' => now()->format('Y'),
            'paymentUrl' => 'initial',
            'ipnResponse' => null,
            'method' => $method, // Or any method you use
            'payment_type' => 'initial',
            'balance' => 0,
            'payment_method' => $payment_method, // Default to 'cash' if not provided
        ]);

        // Update user step
        // $user->activateUser();

        return [
            'success' => true,
            'message' => 'Payment has been successfully created.',
            'payment' => $payment,
        ];



}


function generateTransactionId()
{
    return 'TRX-' . strtoupper(uniqid());
}



function logBrowsingHistory($viewedUserId)
{
    BrowsingHistory::create([
        'user_id' => auth()->id(), // The current user (who is browsing)
        'viewed_user_id' => $viewedUserId, // The user being viewed
        'viewed_at' => now(),
    ]);
}


function getRandomActiveUsers()
{
    // Fetch 4 random users where the status is 'active' and the profile is of type 'EMPLOYEE'
    $randomActiveUsers = Profile::where('status', 'active')  // Profile status is 'active'
        ->where('profile_type', 'EMPLOYEE')  // Filter by 'EMPLOYEE' profile type
        ->with(['user.thumbnail'])  // Load user data and thumbnail relationship
        ->inRandomOrder()  // Randomize the order
        ->take(4)  // Limit to 4 users
        ->get();

    // Return the random active users or an empty array if no users are found
    return $randomActiveUsers->isNotEmpty() ? $randomActiveUsers->toArray() : [];
}
