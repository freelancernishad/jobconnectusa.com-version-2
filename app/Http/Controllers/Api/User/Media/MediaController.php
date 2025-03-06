<?php

namespace App\Http\Controllers\Api\User\Media;

use App\Models\Media;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MediaController extends Controller
{
    /**
     * Store a newly uploaded file.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {


        $validator = Validator::make($request->all(), [
            'file' => 'required|file', // Validate that a file is uploaded
            'is_profile_picture' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $file = $request->file('file'); // Get the uploaded file
        $fileName = time() . '_' . $file->getClientOriginalName(); // Generate a unique file name
        if($request->is_profile_picture){
            $filePath = 'user/profile/' . $fileName; // Path to store the file
        }else{
            $filePath = 'media/' . $fileName; // Path to store the file

        }


        // Store the file in the 'protected' storage directory
        $file->storeAs('/', $filePath, 'protected');

        $media = Media::create([
            'name' => $fileName,
            'url' => $filePath,
            'type' => $file->getMimeType(), // Get MIME type of the file
            'is_profile_picture' => $request->input('is_profile_picture', false),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully.',
            'file_url' => url('/files/' . $media->url),
        ], 201);
    }


    /**
     * Display a listing of all media.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $media = Media::all();

        return response()->json([
            'success' => true,
            'message' => 'Media information retrieved successfully.',
            'media' => $media,
        ], 200);
    }

    /**
     * Display the specified media.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json(['message' => 'Media not found'], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Media information retrieved successfully.',
            'media' => $media,
        ], 200);
    }

    /**
     * Update the specified media.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'file' => 'nullable|file', // Validate that a file is uploaded (if provided)
            'is_profile_picture' => 'nullable|boolean',
        ]);

        $media = Media::find($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'The requested media could not be found. Please check the media ID and try again.',
            ], 404);
        }

        $data = $request->only(['is_profile_picture']);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = 'protected/' . $fileName;

            // Store the new file in the 'protected' storage directory
            $file->storeAs('protected', $fileName, 'protected');

            // Delete the old file if it exists
            if ($media->url && Storage::disk('protected')->exists($media->url)) {
                Storage::disk('protected')->delete($media->url);
            }

            // Update the media record with the new file details
            $data['name'] = $fileName;
            $data['url'] = $filePath;
            $data['type'] = $file->getMimeType();
        }

        $media->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Media updated successfully.',
            'media' => $media,
        ], 200);
    }

    /**
     * Remove the specified media from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json([
                'success' => false,
                'message' => 'The requested media could not be found. Please verify the media ID and try again.',
            ], 404);
        }

        // Delete the file from storage
        Storage::delete($media->url);

        $media->delete();

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully.',
        ], 200);

    }
}
