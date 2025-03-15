<?php

namespace App\Http\Controllers\Api\User\Resume;

use App\Models\User;
use App\Models\Resume;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ResumeController extends Controller
{
    /**
     * Display a listing of the user's resumes.
     */
    public function index()
    {
        $user = auth()->user();
        $resumes = $user->resumes;

        return response()->json([
            'success' => true,
            'message' => 'Resumes retrieved successfully.',
            'resumes' => $resumes,
        ], 200);
    }

    /**
     * Store a newly uploaded resume in storage.
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            // Uncomment and update the validation if needed
            // 'resume' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        // If validation fails, return the errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if the authenticated user is an admin
        if (auth('admin')->check()) {
            // If admin, get the user by the provided user_id in the request
            $user = User::find($request->input('user_id'));

            // If no user is found, return an error response
            if (!$user) {
                return response()->json(['error' => 'User not found.'], 404);
            }
        } else {
            // If not an admin, use the authenticated user
            $user = auth()->user();
        }

        // Check if a file is uploaded in the request
        if ($request->hasFile('resume')) {
            // Store the uploaded resume in the protected storage disk
            $path = $request->file('resume')->store('resumes', 'protected');
        } else {
            return response()->json(['error' => 'No resume file provided.'], 400);
        }

        // Save the resume path in the database for the correct user
        $resume = $user->resumes()->create([
            'resume_path' => $path,
        ]);

        // Return success response with resume details
        return response()->json([
            'success' => true,
            'message' => 'Resume uploaded successfully.',
            'resume' => $resume,
        ], 201);
    }


    /**
     * Display the specified resume.
     */
    public function show($id)
    {
        // Fetch the resume directly by its ID
        $resume = Resume::findOrFail($id);

        // Serve the file from protected storage
        return Storage::disk('protected')->download($resume->resume_path);
    }


    /**
     * Remove the specified resume from storage.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $resume = $user->resumes()->findOrFail($id);

        // Delete the file from storage
        Storage::disk('protected')->delete($resume->resume_path);

        // Delete the resume record from the database
        $resume->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resume deleted successfully.',
        ], 200);
    }


    public function getByAuthenticatedUser()
    {
        // Ensure the user is authenticated
        $authenticatedUser = Auth::guard('api')->user();

        if (!$authenticatedUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Retrieve the resumes for the authenticated user
        $resumes = Resume::where('user_id', $authenticatedUser->id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Resumes retrieved successfully.',
            'resumes' => $resumes,
        ], 200);

    }


}
