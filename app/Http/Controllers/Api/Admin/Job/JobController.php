<?php

namespace App\Http\Controllers\Api\Admin\Job;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    /**
     * Display a listing of the jobs.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $jobs = Job::all();
        return jsonResponse(true, 'Jobs retrieved successfully', $jobs);
    }

    /**
     * Show a single job.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $job = Job::find($id);

        if (!$job) {
            return jsonResponse(false, 'Job not found', null, Response::HTTP_NOT_FOUND);
        }

        return jsonResponse(true, 'Job retrieved successfully', $job);
    }

    /**
     * Add a new job.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {




        // Validate the request data
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'employment_type' => 'required|in:part-time,full-time,both',
            'hourly_rate_min' => 'required|numeric|min:0',
            'hourly_rate_max' => 'required|numeric|min:0',
            'total_positions' => 'required|integer|min:1',
            'status' => 'sometimes|in:open,closed', // 'sometimes' allows default value
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $data = [
            'company_name' => $request->company_name,
            'location' => $request->location,
            'position' => $request->position,
            'employment_type' => $request->employment_type,
            'hourly_rate_min' => $request->hourly_rate_min,
            'hourly_rate_max' => $request->hourly_rate_max,
            'total_positions' => $request->total_positions,
            'status' => 'open', // Default status
            'website' => $request->website, // Optional field
            'company_logo' => $request->company_logo, // Optional field
            'created_by' => '', // Set created_by initially
        ];

        // Check the guard for the authenticated user
        if (auth()->guard('admin')->check()) {
            $admin = auth()->guard('admin')->user();
            $data['admin_id'] = $admin->id; // Fill admin_id
            $data['user_id'] = null; // Set user_id to null for admin
            $data['created_by'] = 'admin'; // Set created_by to admin's name
        } elseif (auth()->guard('user')->check()) {
            $user = auth()->guard('user')->user();
            $data['admin_id'] = null; // Set admin_id to null for user
            $data['user_id'] = $user->id; // Fill user_id
            $data['created_by'] = 'user'; // Set created_by to user's name
        } else {
            return jsonResponse(false, 'Unauthorized', null, Response::HTTP_UNAUTHORIZED);
        }

        // Create the job
        $job = Job::create($data);

        return jsonResponse(true, 'Job created successfully', $job, Response::HTTP_CREATED);
    }



    /**
     * Update an existing job.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $job = Job::find($id);

        if (!$job) {
            return jsonResponse(false, 'Job not found', null, Response::HTTP_NOT_FOUND);
        }

        $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'location' => 'sometimes|string|max:255',
            'position' => 'sometimes|string|max:255',
            'employment_type' => 'sometimes|in:part-time,full-time,both',
            'hourly_rate_min' => 'sometimes|numeric|min:0',
            'hourly_rate_max' => 'sometimes|numeric|min:0',
            'total_positions' => 'sometimes|integer|min:1',
        ]);

        // Update only the fields that are present in the request
        $job->update($request->only([
            'company_name',
            'location',
            'position',
            'employment_type',
            'hourly_rate_min',
            'hourly_rate_max',
            'total_positions',
            'website',
            'company_logo'
        ]));

        return jsonResponse(true, 'Job updated successfully', $job);
    }

    /**
     * Delete a job.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $job = Job::find($id);

        if (!$job) {
            return jsonResponse(false, 'Job not found', null, Response::HTTP_NOT_FOUND);
        }

        $job->delete();

        return jsonResponse(true, 'Job deleted successfully');
    }

    /**
     * Change the status of a job.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus($id)
    {
        $job = Job::find($id);

        if (!$job) {
            return jsonResponse(false, 'Job not found', null, Response::HTTP_NOT_FOUND);
        }

        $job->status = $job->status === 'open' ? 'closed' : 'open';
        $job->save();

        return jsonResponse(true, 'Job status updated successfully', ['status' => $job->status]);
    }

}
