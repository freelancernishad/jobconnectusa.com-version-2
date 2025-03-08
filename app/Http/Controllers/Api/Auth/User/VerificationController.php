<?php

namespace App\Http\Controllers\Api\Auth\User;

use App\Models\User;
use App\Models\Profile;
use App\Mail\VerifyEmail;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\OtpNotification;
use Illuminate\Routing\Controller;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

use App\Mail\RegistrationSuccessful;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{

    public function verifyEmail(Request $request, $hash)
    {
        // Find the user by the hash
        $user = User::where('email_verification_hash', $hash)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid or expired verification link.'], 400);
        }

        // Check if the email is already verified
        if ($user->hasVerifiedEmail()) {
            // Generate a new token for the user
            $token = JWTAuth::fromUser($user);

            return response()->json([
                'message' => 'Email already verified.',
                'user' => [
                    'email' => $user->email,
                    'name' => $user->name,
                    'username' => $user->username,
                    'step' => $user->step,
                    'email_verified' => true, // Email was already verified
                ],
                'token' => $token // Return the new token
            ], 200);
        }

        // If not verified, verify the user's email
        $user->markEmailAsVerified();

        // Generate a new token for the user after verification
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
                'username' => $user->username,
                'step' => $user->step,
                'email_verified' => true, // Email was already verified
            ],
            'token' => $token // Return the new token
        ], 200);
    }




    public function verifyOtp(Request $request)
{
    // Validate the request
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
        'otp' => 'required|digits:6', // Validate OTP as 6 digits
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }




    // Find the user by email
    $user = User::where('email', $request->email)->first();

    // Check if the provided OTP matches the stored OTP
    if (!Hash::check($request->otp, $user->otp)) {
        return response()->json(['error' => 'Invalid OTP. Please check the OTP and try again.'], 400);
    }


    // Check if the email is already verified
    if ($user->hasVerifiedEmail()) {
        return $this->generateResponse($user, 'Your email is already verified. You can proceed to use your account.');
    }

    // Check if the OTP has expired
    if ($user->otp_expires_at && $user->otp_expires_at < now()) {
        return response()->json(['error' => 'OTP has expired. Please request a new OTP.'], 400);
    }

    // Verify the user's email
    $user->markEmailAsVerified();

    // Clear the OTP and expiration time
    $user->update([
        'otp' => null,
        'otp_expires_at' => null,
    ]);

    // Generate a new token for the user after verification
    $token = JWTAuth::fromUser($user);

    // Send registration success email (if needed)
    // Mail::to($user->email)->send(new RegistrationSuccessful(['name' => $user->name]));

    return response()->json([
        'message' => 'Email verified successfully.',
        'user' => $this->getUserPayload($user),
        'token' => $token,
    ], 200);
}

/**
 * Generate a standardized response for already verified users.
 */
private function generateResponse($user, $message)
{
    $token = JWTAuth::fromUser($user);
    return response()->json([
        'message' => $message,
        'user' => $this->getUserPayload($user),
        'token' => $token,
    ], 200);
}

/**
 * Get the user payload for the response.
 */
private function getUserPayload($user)
{
    $profile = Profile::where(['user_id' => $user->id, 'profile_type' => $user->active_profile])->first();

    return [
        'username' => $user->username,
        'email' => $user->email,
        'name' => $user->name,
        'active_profile' => $user->active_profile,
        'active_profile_id' => $user->active_profile_id,
        'step' => $profile ? (int)$profile->step : 1,
        'status' => $profile ? $profile->status : 'inactive',
        'email_verified' => $user->hasVerifiedEmail(),
    ];
}



    public function resendVerificationLink(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            // Optionally validate verify_url if it's part of the request
            'verify_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists and if the email is not already verified
        if (!$user || $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is either already verified or user does not exist.'], 400);
        }

        // Generate a new verification token
        $verificationToken = Str::random(60); // Generate a unique token
        $user->email_verification_hash = $verificationToken;
        $user->save();

        // Build the new verification URL
        $verify_url = $request->verify_url;

        // Resend the verification email
        Mail::to($user->email)->send(new VerifyEmail($user, $verify_url));
    //    return response()->json($emailstatus);
        return response()->json(['message' => 'Verification link has been sent.'], 200);
    }


    public function resendOtp(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the user by email
        $user = User::where('email', $request->email)->first();

        // Check if the user exists and if the email is not already verified
        if (!$user || $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is either already verified or user does not exist.'], 400);
        }

        // Generate a new 6-digit numeric OTP
        $otp = random_int(100000, 999999); // Generates a random integer between 100000 and 999999
        $user->otp = Hash::make($otp); // Store hashed OTP
        $user->otp_expires_at = now()->addMinutes(5); // Set expiration time
        $user->save();

        // Send the new OTP via email
        Mail::to($user->email)->send(new OtpNotification($user,$otp));

        return response()->json(['message' => 'A new OTP has been sent to your email.'], 200);
    }




}
