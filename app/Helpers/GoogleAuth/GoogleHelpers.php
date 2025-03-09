<?php

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

function handleGoogleAuth(Request $request)
{
    // Validate the access token
    $validator = Validator::make($request->all(), [
        'access_token' => 'required|string',
        'role' => 'required|string|in:EMPLOYEE,employer', // Validate role input
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Fetch user data from Google API
        $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'access_token' => $request->access_token,
        ]);

        if ($response->failed() || !isset($response['email'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid access token.',
            ], 400);
        }

        $userData = $response->json();
        $user = User::where('email', $userData['email'])->first();

        if (!$user) {
            // Register the user if they don't exist
            $user = User::create([
                'name' => $userData['name'] ?? explode('@', $userData['email'])[0],
                'email' => $userData['email'],
                'password' => Hash::make(Str::random(16)), // Generate a random password
                'email_verified_at' => now(),
                'active_profile' => $request->role === 'employer' ? 'employer' : 'EMPLOYEE', // Set active_profile based on role
            ]);
        } else {
            // Update the user's email verification status and role if necessary
            $user->update([
                'email_verified_at' => now(),
                'active_profile' => $request->role === 'employer' ? 'employer' : 'EMPLOYEE', // Update active_profile
            ]);
        }

        // Authenticate the user
        Auth::login($user);


        $step = 1;
        $checkProfile = Profile::find($user->active_profile_id);
        if($checkProfile){
            $step = $checkProfile->step;
        }

        // Custom payload data
        $payload = [
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name,
            'active_profile' => $user->active_profile,
            'step' => $step,
            'email_verified' => $user->hasVerifiedEmail(),
        ];

        try {
            // Generate a JWT token with custom claims
            $token = JWTAuth::fromUser($user, ['guard' => 'user']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'An error occurred during authentication.',
            'details' => $e->getMessage(),
        ], 500);
    }
}
