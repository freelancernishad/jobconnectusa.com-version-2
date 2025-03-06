<?php

namespace App\Http\Controllers\Api\User\Thumbnail;

use App\Http\Controllers\Controller;
use App\Models\Thumbnail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ThumbnailController extends Controller
{
    /**
     * Display a listing of the thumbnails.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user(); // Get the authenticated user
        $thumbnail = Thumbnail::where('user_id', $user->id)->latest()->first(); // Get the most recent thumbnail for the authenticated user
        return response()->json($thumbnail);
    }



    /**
     * Store a newly created thumbnail in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Extract user ID from JWT token
        $userId = auth()->user()->id;

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // Validate file upload for file_path
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle file upload for file_path
        $filePath = $request->file('file_path')->store('thumbnails', 'protected');

        // Check if a thumbnail already exists for this user
        $thumbnail = Thumbnail::where('user_id', $userId)->first();

        if ($thumbnail) {
            // Update existing thumbnail
            $thumbnail->update([
                'file_path' => url('files/'.$filePath),
            ]);
        } else {
            // Create new thumbnail
            $thumbnail = Thumbnail::create([
                'user_id' => $userId,
                'file_path' => url('files/'.$filePath),
            ]);
        }

        return response()->json($thumbnail, $thumbnail->wasRecentlyCreated ? 201 : 200);
    }



    /**
     * Update the specified thumbnail in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Thumbnail  $thumbnail
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Thumbnail $thumbnail)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $thumbnail->update($request->all());

        return response()->json($thumbnail);
    }

    /**
     * Remove the specified thumbnail from storage.
     *
     * @param  \App\Models\Thumbnail  $thumbnail
     * @return \Illuminate\Http\Response
     */
    public function destroy(Thumbnail $thumbnail)
    {
        $thumbnail->delete();
        return response()->json(['message' => 'Thumbnail deleted successfully']);
    }
}
