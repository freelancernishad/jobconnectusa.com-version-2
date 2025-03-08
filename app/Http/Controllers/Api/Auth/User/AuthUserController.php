<?php

namespace App\Http\Controllers\Api\Auth\User;

use App\Models\User;
use App\Models\Profile;
use App\Mail\VerifyEmail;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use App\Mail\OtpNotification;


use App\Models\TokenBlacklist;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;

class AuthUserController extends Controller
{
    /**
     * Register a new user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        if ($request->access_token) {
            return handleGoogleAuth($request);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:employee,employer', // Validate role input
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Create the user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active_profile' => $request->role === 'employer' ? 'employer' : 'employee', // Set active_profile based on role
        ]);

        // Generate a JWT token for the newly created user
        try {
            $token = JWTAuth::fromUser($user, ['guard' => 'user']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Generate verification URL (if applicable)
        $verify_url = $request->verify_url ?? null; // Optional verify URL from the request

        // Notify user for email verification
        try {
            if ($verify_url) {
                Mail::to($user->email)->send(new VerifyEmail($user, $verify_url));
            } else {
                // Generate a 6-digit numeric OTP
                $otp = random_int(100000, 999999); // Generate OTP
                $user->otp = Hash::make($otp); // Store hashed OTP
                $user->otp_expires_at = now()->addMinutes(5); // Set OTP expiration time
                $user->save();

                // Notify user with the OTP
                Mail::to($user->email)->send(new OtpNotification($otp));
            }
        } catch (\Exception $e) {
            // Log the email sending error
            \Log::error('Email sending failed: ' . $e->getMessage());

            // Optionally, you can notify the admin or take other actions here
        }

        // Define payload data
        $payload = [
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name,
            'active_profile' => $user->active_profile,
            'step' => 1,
            'email_verified' => $user->hasVerifiedEmail(),
        ];

        return response()->json([
            'token' => $token,
            'user' => $payload,
        ], 201);
    }


    /**
     * Log in a user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
{
    // Handle Google Auth if access_token is provided
    if ($request->access_token) {
        return handleGoogleAuth($request);
    }

    // Validate the request data
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|email',
        'password' => 'required|string',
        'role' => 'required|string|in:employee,employer', // Validate role
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Attempt to authenticate the user
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // Update active_profile based on role
        if ($request->role === 'employee') {
            $user->active_profile = 'employee';
        } elseif ($request->role === 'employer') {
            $user->active_profile = 'employer';
        }


        // Determine the step based on the active profile
        $step = 1;
        $checkProfile = Profile::where(['profile_type'=>$user->active_profile])->first();

        // return response()->json($checkProfile);

        if ($checkProfile) {
            $step = (int)$checkProfile->step;
            $user->active_profile_id = $checkProfile->id;
            $checkProfileStatus = $checkProfile->status;
        }else{
            $active_profile_id = null;
            $user->active_profile_id = $active_profile_id;
            $checkProfileStatus = 'inactive';
        }
        $user->save();
        // Custom payload data, including email verification status
        $payload = [
            'username' => $user->username,
            'email' => $user->email,
            'name' => $user->name,
            'active_profile' => $user->active_profile,
            'active_profile_id' => $user->active_profile_id,
            'step' => $step,
            'status' => $checkProfileStatus,
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
    }

    return response()->json(['message' => 'Invalid credentials'], 401);
}



    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {

        return response()->json(Auth::user());
    }

    /**
     * Log out the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Get the Bearer token from the Authorization header
        $token = $request->bearerToken();

        // Check if the token is present
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided.'
            ], 401);
        }

        // Proceed with token invalidation
        try {
            TokenBlacklist($token);
            JWTAuth::setToken($token)->invalidate();
            // Store the token in the blacklist

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while processing token: ' . $e->getMessage()
            ], 500);
        }
    }

  /**
     * Change the password of the authenticated user.
     */
    public function changePassword(Request $request)
    {
        // Validate input using Validator
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 400);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully.']);
    }


    /**
     * Check if a JWT token is valid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        $token = $request->bearerToken(); // Get the token from the Authorization header

        if (!$token) {
            return response()->json(['message' => 'Token not provided.'], 400);
        }

        try {
            // Authenticate the token and retrieve the authenticated user
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Token is invalid or user not found.'], 401);
            }

            $payload = [
                'email' => $user->email,
                'name' => $user->name,
                'email_verified' => $user->hasVerifiedEmail(), // Checks verification status
            ];

            return response()->json(['message' => 'Token is valid.','user'=>$payload], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token is missing or malformed.'], 401);
        }
    }

}
