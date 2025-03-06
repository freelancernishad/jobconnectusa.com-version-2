<?php

namespace App\Http\Controllers\Global;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\BrowsingHistory;
use App\Models\Service;

class BrowsingHistoryController extends Controller
{


    public function recommendUsersWithFilters(Request $request)
    {
        $userId = auth()->id();  // Get the ID of the currently logged-in user

        // Get recently viewed users by this user, sorted by how recently they were viewed, only active ones, and with role "EMPLOYEE"
        $recentlyViewedUsers = BrowsingHistory::where('user_id', $userId)
            ->with(['viewedUser' => function ($query) use ($userId) {
                $query->where('status', 'active')  // Fetch only active users
                    ->where('role', 'EMPLOYEE')    // Fetch only users with role "EMPLOYEE"
                    ->where('id', '!=', $userId)   // Exclude the current authenticated user
                    ->with(['thumbnail']);
            }])
            ->orderBy('viewed_at', 'desc')
            ->take(10)  // Limit to 10 recently viewed users
            ->get()
            ->pluck('viewedUser')  // Extract the users themselves
            ->filter()
            ->unique('id')  // Ensure uniqueness by user ID
            ->values();  // Re-index the collection to remove the original keys

        // Apply pagination if requested
        if ($request->has('per_page')) {
            $perPage = (int) $request->get('per_page');
            $finalRecommendations = $recentlyViewedUsers->forPage(1, $perPage);  // Paginate the collection manually
        }
        // Apply limit if requested
        elseif ($request->has('limit')) {
            $limit = (int) $request->get('limit');
            $finalRecommendations = $recentlyViewedUsers->take($limit);
        }
        // Default to fetching all recently viewed users (with a maximum limit)
        else {
            $finalRecommendations = $recentlyViewedUsers->take(4);  // Default limit of 4
        }

        // Convert the collection to an array for the JSON response
        return response()->json([
            'success' => true,
            'message' => 'Recommended users based on your browsing history!',
            'data' => $finalRecommendations->isNotEmpty() ? $finalRecommendations->toArray() : getRandomActiveUsers(),
        ]);
    }







}
