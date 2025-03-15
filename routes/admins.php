<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Api\Admin\Job\JobController;
use App\Http\Controllers\Api\User\Media\MediaController;
use App\Http\Controllers\Api\Admin\Job\JobApplyController;
use App\Http\Controllers\Api\User\Resume\ResumeController;
use App\Http\Controllers\Api\Auth\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\Service\ServiceController;
use App\Http\Controllers\Api\Admin\Users\AdminUserController;
use App\Http\Controllers\Api\Admin\SkillList\SkillListController;
use App\Http\Controllers\Api\Admin\Hiring\HiringProcessController;
use App\Http\Controllers\Api\Admin\Transaction\TransactionController;
use App\Http\Controllers\Api\Admin\Hiring\EmployeeHiringPriceController;

// Admin Authentication Routes
Route::prefix('auth/admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login');
    Route::post('register', [AdminAuthController::class, 'register']);

    Route::middleware(AuthenticateAdmin::class)->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('change-password', [AdminAuthController::class, 'changePassword']);
        Route::get('check-token', [AdminAuthController::class, 'checkToken']);
    });
});

// Admin Routes (Protected by AuthenticateAdmin Middleware)
Route::prefix('admin')->middleware(AuthenticateAdmin::class)->group(function () {

    // Service Routes
    Route::prefix('services')->group(function () {
        Route::get('/', [ServiceController::class, 'index']);
        Route::get('{id}', [ServiceController::class, 'show']);
        Route::post('/', [ServiceController::class, 'store']);
        Route::put('{id}', [ServiceController::class, 'update']);
        Route::delete('{id}', [ServiceController::class, 'destroy']);
    });

    // Skill List Routes
    Route::prefix('skill-lists')->group(function () {
        Route::get('/', [SkillListController::class, 'index']);
        Route::get('{id}', [SkillListController::class, 'show']);
        Route::post('/', [SkillListController::class, 'store']);
        Route::put('{id}', [SkillListController::class, 'update']);
        Route::delete('{id}', [SkillListController::class, 'destroy']);
        Route::get('/services/{serviceId}', [SkillListController::class, 'listByService']);
    });

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
                Route::post('/{id}', [MediaController::class, 'update']);
                Route::delete('/{id}', [MediaController::class, 'destroy']);
            });



    // User Payment Approval Routes
    Route::get('users-with-pending-payments', [AdminUserController::class, 'getUsersWithPendingPayments']);
    Route::post('approve-payment/{paymentId}', [AdminUserController::class, 'approvePayment']);
    Route::post('cancel-payment/{paymentId}', [AdminUserController::class, 'cancelPayment']);

    // Hiring Requests Routes
    Route::prefix('hiring-requests')->group(function () {
        Route::get('/step/{step}', [HiringProcessController::class, 'getRequestsByStep']);
        Route::get('/', [HiringProcessController::class, 'getAllRequests']);
        Route::get('/employer/{employerId}', [HiringProcessController::class, 'getRequestsByEmployer']);
        Route::get('/step/{step}/pagination', [HiringProcessController::class, 'getRequestsByStepWithPagination']);
        Route::post('/{id}/assign', [HiringProcessController::class, 'assignEmployee']);
        Route::post('/assignments/{id}/release', [HiringProcessController::class, 'releaseEmployee']);
        Route::get('/{id}', [HiringProcessController::class, 'getHiringRequest']);
    });

    // Employee Hiring Price Routes
    Route::prefix('employee-hiring-prices')->group(function () {
        Route::post('/', [EmployeeHiringPriceController::class, 'store']);
        Route::put('{employeeHiringPrice}', [EmployeeHiringPriceController::class, 'update']);
        Route::delete('{employeeHiringPrice}', [EmployeeHiringPriceController::class, 'destroy']);
    });

    // Transactions Routes
    Route::prefix('transactions')->group(function () {
        Route::get('/all', [TransactionController::class, 'getAllTransactions']);
        Route::get('/by-type', [TransactionController::class, 'getTransactionsByType']);
        Route::get('/by-user/{userId}', [TransactionController::class, 'getTransactionsByUser']);
    });

    // Admin User Search
    Route::get('user/search', [AdminUserController::class, 'getUsersByRole']);

    // Job Routes
    Route::prefix('jobs')->group(function () {
        Route::get('/', [JobController::class, 'index']);
        Route::get('{id}', [JobController::class, 'show']);
        Route::post('/', [JobController::class, 'store']);
        Route::put('{id}', [JobController::class, 'update']);
        Route::delete('{id}', [JobController::class, 'destroy']);
        Route::patch('{id}/change-status', [JobController::class, 'changeStatus']);
    });

    // Job Applications Routes
    Route::get('job-applies', [JobApplyController::class, 'getJobApplies']);




    // version 2 features

    Route::post('create/profile', [AdminUserController::class, 'createUserAndProfile']);








});

// Transaction Route (Outside Admin Middleware)
Route::get('/transactions/hiring-request/{hiringRequestId}', [TransactionController::class, 'getTransactionByHiringRequestId']);
