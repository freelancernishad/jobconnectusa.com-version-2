<?php


namespace App\Http\Controllers\Api\Global;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\User;

class LikeController extends Controller
{
    // Like a user
    public function likeUser(Request $request)
    {
        // Validate the request
        $request->validate([
            'liked_user_id' => 'required|exists:users,id',
        ]);

        $userId = auth()->id();  // Get the ID of the currently logged-in user
        $likedUserId = $request->input('liked_user_id');

        // Check if the user has already liked the other user
        $existingLike = Like::where('user_id', $userId)
                            ->where('liked_user_id', $likedUserId)
                            ->first();

        if ($existingLike) {
            return response()->json([
                'success' => false,
                'message' => 'User already liked.',
            ], 400);
        }

        // Create a new like record
        Like::create([
            'user_id' => $userId,
            'liked_user_id' => $likedUserId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User liked successfully.',
        ], 200);
    }

    // Get liked users for the current user
  // app/Http/Controllers/Global/YourController.php

public function getLikedUsers()
{
    // Get the ID of the currently logged-in user
    $currentUserId = auth()->id();

    // Fetch users that the currently authenticated user has liked
    $likedUsers = User::whereIn('id', function ($query) use ($currentUserId) {
        $query->select('liked_user_id')
              ->from('likes')
              ->where('user_id', $currentUserId);
    })
    ->with([
        'thumbnail'
        // 'languages',
        // 'certifications',
        // 'skills',
        // 'education',
        // 'employmentHistory',
        // 'resumes',
        // 'hiringSelections',
        // 'hiringAssignments',
        // 'assignedHiringAssignments',
        // 'servicesLookingFor'
    ])
    ->get()
    ->map(function ($user) use ($currentUserId) {
        // Add properties to each user
        $user->user_liked_by_current_user = $user->isLikedByUser($currentUserId);
        $user->total_likes_received = $user->receivedLikes()->count();
        return $user;
    });

    return response()->json([
        'success' => true,
        'message' => 'Liked users retrieved successfully.',
        'data' => $likedUsers,
    ], 200);
}


}
