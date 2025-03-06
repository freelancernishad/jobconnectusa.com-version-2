<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Global\LikeController;
use App\Http\Controllers\Api\User\Media\MediaController;
use App\Http\Controllers\Api\Admin\Job\JobApplyController;
use App\Http\Controllers\Api\Auth\User\AuthUserController;
use App\Http\Controllers\Api\User\Resume\ResumeController;
use App\Http\Controllers\Global\BrowsingHistoryController;
use App\Http\Controllers\Api\User\Thumbnail\ThumbnailController;
use App\Http\Controllers\Api\User\UserManagement\UserController;
use App\Http\Controllers\Api\Admin\Hiring\HiringProcessController;

// Routes for authentication (login, register, logout, etc.)
Route::prefix('auth/user')->group(function () {
    Route::post('login', [AuthUserController::class, 'login'])->name('login'); // User login
    Route::post('register', [AuthUserController::class, 'register']); // User registration

    // Group of routes that require user authentication
    Route::middleware(AuthenticateUser::class)->group(function () {
        Route::post('logout', [AuthUserController::class, 'logout']); // Logout
        Route::get('me', [AuthUserController::class, 'me']); // Get authenticated user data
        Route::post('change-password', [AuthUserController::class, 'changePassword']); // Change user password
        Route::get('check-token', [AuthUserController::class, 'checkToken']); // Check token validity
    });
});

// Routes for user-related actions (resume, media, etc.)
Route::prefix('user')->group(function () {
    // Group of routes that require user authentication
    Route::middleware(AuthenticateUser::class)->group(function () {

        // User registration steps
        Route::post('/user/register/step2', [UserController::class, 'registerStep2']);
        Route::post('/user/register/step3', [UserController::class, 'registerStep3']);

        // Fetch user data by username and update profile by token
        Route::get('/user/{username}', [UserController::class, 'getUserByUsername']);
        Route::post('/user/update/profile', [UserController::class, 'updateProfileByToken']);

        // Resume management (create, update, delete, download)
        Route::get('/resumes', [ResumeController::class, 'index']);
        Route::post('/resumes', [ResumeController::class, 'store']);
        Route::post('/resumes/download/{id}', [ResumeController::class, 'show']);
        Route::delete('/resumes/{id}', [ResumeController::class, 'destroy']);
        Route::get('/authenticated/user/resumes', [ResumeController::class, 'getByAuthenticatedUser']);

        // Media management (upload, fetch, update, delete)
        Route::prefix('media')->group(function () {
            Route::post('/', [MediaController::class, 'store']);
            Route::get('/', [MediaController::class, 'index']);
            Route::get('/{id}', [MediaController::class, 'show']);
            Route::put('/{id}', [MediaController::class, 'update']);
            Route::delete('/{id}', [MediaController::class, 'destroy']);
        });

        // Create a hiring request
        Route::post('/hiring-request', [HiringProcessController::class, 'createHiringRequest']);

        // Route for recommending users based on filters and browsing history
        Route::get('/recommend-users-with-filters', [BrowsingHistoryController::class, 'recommendUsersWithFilters']);

        // Like/unlike functionality for users
        Route::post('/like-user', [LikeController::class, 'likeUser']);
        Route::get('/liked-users', [LikeController::class, 'getLikedUsers']);

        // Thumbnail management (upload, fetch, update, delete)
        Route::prefix('thumbnails')->group(function () {
            Route::get('/', [ThumbnailController::class, 'index']);
            Route::post('/', [ThumbnailController::class, 'store']);
            Route::put('/{thumbnail}', [ThumbnailController::class, 'update']);
            Route::delete('/{thumbnail}', [ThumbnailController::class, 'destroy']);
        });

        // Get employers that the user may be interested in
        Route::get('/employers-you-may-like', [UserController::class, 'getEmployeesYouMayLike']);

        // Change password route (admin-level permission required)
        Route::post('users/change-password', [UserController::class, 'changePassword'])
            ->name('users.change_password')
            ->middleware('checkPermission:users.change_password');

        // Apply for a job
        Route::post('job/apply', [JobApplyController::class, 'sendMail']);

        // User payment cancellation route
        Route::prefix('user')->group(function () {
            Route::post('cancel/payment', [UserController::class, 'cancelPaymentByUserId']);
        });

        // Example of user access with permissions
        Route::get('/user-access', function () {
            return 'user access';
        })->name('user.access')->middleware('checkPermission:user.access');
    });
});
