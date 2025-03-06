<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Api\Admin\Job\JobController;
use App\Http\Controllers\Api\Admin\Job\JobApplyController;
use App\Http\Controllers\Api\Auth\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\Service\ServiceController;
use App\Http\Controllers\Api\Admin\Users\AdminUserController;
use App\Http\Controllers\Api\Admin\SkillList\SkillListController;
use App\Http\Controllers\Api\Admin\Hiring\HiringProcessController;
use App\Http\Controllers\Api\Admin\Transaction\TransactionController;
use App\Http\Controllers\Api\Admin\Hiring\EmployeeHiringPriceController;



Route::prefix('auth/admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login');
    Route::post('register', [AdminAuthController::class, 'register']);

    Route::middleware(AuthenticateAdmin::class)->group(function () { // Applying admin middleware
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
        Route::get('check-token', [AdminAuthController::class, 'checkToken']);

    });
});


Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () {

        Route::get('services', [ServiceController::class, 'index']);
        Route::get('services/{id}', [ServiceController::class, 'show']);
        Route::post('services', [ServiceController::class, 'store']);
        Route::put('services/{id}', [ServiceController::class, 'update']);
        Route::delete('services/{id}', [ServiceController::class, 'destroy']);



        Route::get('skill-lists', [SkillListController::class, 'index']);
        Route::get('skill-lists/{id}', [SkillListController::class, 'show']);
        Route::post('skill-lists', [SkillListController::class, 'store']);
        Route::put('skill-lists/{id}', [SkillListController::class, 'update']);
        Route::delete('skill-lists/{id}', [SkillListController::class, 'destroy']);
        Route::get('services/{serviceId}/skill-lists', [SkillListController::class, 'listByService']);



        Route::get('users-with-pending-payments', [AdminUserController::class, 'getUsersWithPendingPayments']);
        Route::post('approve-payment/{paymentId}', [AdminUserController::class, 'approvePayment']);
        Route::post('cancel-payment/{paymentId}', [AdminUserController::class, 'cancelPayment']);





        // Route to get requests by step
        Route::get('/hiring-requests/step/{step}', [HiringProcessController::class, 'getRequestsByStep']);

        // Route to get all hiring requests (admin only)
        Route::get('/hiring-requests', [HiringProcessController::class, 'getAllRequests']);

        // Route to get requests by employer
        Route::get('/hiring-requests/employer/{employerId}', [HiringProcessController::class, 'getRequestsByEmployer']);

        // Route to get requests by step with pagination
        Route::get('/hiring-requests/step/{step}/pagination', [HiringProcessController::class, 'getRequestsByStepWithPagination']);



        Route::post('/hiring-request/{id}/assign', [HiringProcessController::class, 'assignEmployee']);

        Route::post('/hiring-assignments/{id}/release', [HiringProcessController::class, 'releaseEmployee']);

        Route::get('/hiring-request/{id}', [HiringProcessController::class, 'getHiringRequest']);



        Route::prefix('employee-hiring-prices')->group(function () {
            Route::post('/', [EmployeeHiringPriceController::class, 'store']); // Create new record
            Route::put('/{employeeHiringPrice}', [EmployeeHiringPriceController::class, 'update']); // Update a record
            Route::delete('/{employeeHiringPrice}', [EmployeeHiringPriceController::class, 'destroy']); // Delete a record
        });


        Route::prefix('transactions')->group(function () {
            Route::get('/all', [TransactionController::class, 'getAllTransactions']);
            Route::get('/by-type', [TransactionController::class, 'getTransactionsByType']);
            Route::get('/by-user/{userId}', [TransactionController::class, 'getTransactionsByUser']);
        });


        Route::get('admin/users/search', [AdminUserController::class, 'getUsersByRole']);





        Route::get('jobs', [JobController::class, 'index']);
        Route::get('jobs/{id}', [JobController::class, 'show']);
        Route::post('jobs', [JobController::class, 'store']);
        Route::put('jobs/{id}', [JobController::class, 'update']);
        Route::delete('jobs/{id}', [JobController::class, 'destroy']);
        Route::patch('jobs/{id}/change-status', [JobController::class, 'changeStatus']);


        Route::get('admin/job-applies', [JobApplyController::class, 'getJobApplies']);





    });
});

Route::get('/transactions/hiring-request/{hiringRequestId}', [TransactionController::class, 'getTransactionByHiringRequestId']);
