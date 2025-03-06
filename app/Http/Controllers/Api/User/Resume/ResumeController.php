<?php

namespace App\Http\Controllers\Api\User\Resume;

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
        $validator = Validator::make($request->all(), [
            // 'resume' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = auth()->user();

        // Store the uploaded resume in the protected storage
        $path = $request->file('resume')->store('resumes', 'protected');

        // Save the resume path in the database
        $resume = $user->resumes()->create([
            'resume_path' => $path,
        ]);

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
