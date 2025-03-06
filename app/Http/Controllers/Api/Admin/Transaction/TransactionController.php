<?php

namespace App\Http\Controllers\Api\Admin\Transaction;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TransactionController extends Controller
{
    /**
     * Get all approved transactions with optional pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTransactions(Request $request)
    {
        // Determine the number of items per page (default to 10 if not provided)
        $perPage = $request->query('per_page', 10);

        // Retrieve trxId from the request query parameters
        $trxId = $request->query('trxId');

        // Start building the query
        $query = Payment::with([
                'user.languages',           // Eager load user's languages
                'user.certifications',      // Eager load user's certifications
                'user.skills',              // Eager load user's skills
                'user.education',           // Eager load user's education
                'user.employmentHistory',   // Eager load user's employment history
                'user.resume',              // Eager load user's resume
                'hiringRequest'             // Eager load hiring request related to transaction
            ])
            ->where('status', 'approved') // Filter by approved status
            ->orderBy('id', 'desc'); // Sort by ID in descending order

        // If trxId exists, add it as a filter
        if ($trxId) {
            $query->where('trxId', $trxId); // Filter by trxId
        }

        // Paginate the results
        $transactions = $query->paginate($perPage);

        // Return response using the jsonResponse function
        if ($transactions->isEmpty()) {
            return jsonResponse(false, 'No approved transactions found.', [], 404);
        }

        return jsonResponse(true, 'Approved transactions retrieved successfully.', $transactions);
    }


    /**
     * Get approved transactions filtered by type with default 'Hiring-Request', with optional pagination
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionsByType(Request $request)
    {
        // Get the type from the query parameter, default to 'Hiring-Request'
        $type = $request->query('type', 'Hiring-Request');

        // Determine the number of items per page (default to 10 if not provided)
        $perPage = $request->query('per_page', 10);

        // Fetch approved transactions based on the type, eager load relations, and paginate them
        $transactions = Payment::with([
                'user.languages',           // Eager load user's languages
                'user.certifications',      // Eager load user's certifications
                'user.skills',              // Eager load user's skills
                'user.education',           // Eager load user's education
                'user.employmentHistory',   // Eager load user's employment history
                'user.resume',              // Eager load user's resume
                'hiringRequest'             // Eager load hiring request related to transaction
            ])
            ->where('status', 'approved') // Filter by approved status
            ->where('type', $type)
            ->orderBy('id', 'desc') // Sort by ID in descending order
            ->paginate($perPage);

        // Return response using the jsonResponse function
        if ($transactions->isEmpty()) {
            return jsonResponse(false, "No approved transactions found for type: $type.", [], 404);
        }

        return jsonResponse(true, "Approved transactions for type: $type retrieved successfully.", $transactions);
    }

    /**
     * Get approved transactions filtered by user ID with optional pagination
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionsByUser(Request $request, $userId)
    {
        // Determine the number of items per page (default to 10 if not provided)
        $perPage = $request->query('per_page', 10);

        // Fetch approved transactions for a specific user, eager load relations, and paginate them
        $transactions = Payment::with([
                'user.languages',           // Eager load user's languages
                'user.certifications',      // Eager load user's certifications
                'user.skills',              // Eager load user's skills
                'user.education',           // Eager load user's education
                'user.employmentHistory',   // Eager load user's employment history
                'user.resume',              // Eager load user's resume
                'hiringRequest'             // Eager load hiring request related to transaction
            ])
            ->where('status', 'approved') // Filter by approved status
            ->where('userid', $userId)
            ->orderBy('id', 'desc') // Sort by ID in descending order
            ->paginate($perPage);

        // Return response using the jsonResponse function
        if ($transactions->isEmpty()) {
            return jsonResponse(false, "No approved transactions found for user ID: $userId.", [], 404);
        }

        return jsonResponse(true, "Approved transactions for user ID: $userId retrieved successfully.", $transactions);
    }


    public function getTransactionByHiringRequestId(Request $request, $hiringRequestId)
    {
        // Check if the request is from an authenticated admin
        $isAdmin = auth('admin')->check();

        // Check if the request is from an authenticated API user
        $isApiUser = auth('api')->check();

        // If neither admin nor API user is authenticated, return an error
        if (!$isAdmin && !$isApiUser) {
            return jsonResponse(false, "Unauthenticated.", [], 401);
        }

        // Fetch the approved transaction for a specific hiring request ID, eager load relations
        $transaction = Payment::with([
                'employer.servicesLookingFor',
                'hiringRequest',
            ])
            ->where('status', 'approved') // Filter by approved status
            ->where('hiring_request_id', $hiringRequestId) // Filter by hiring request ID
            ->orderBy('id', 'desc') // Sort by ID in descending order
            ->first(); // Get the first transaction that matches the criteria

        // Return response using the jsonResponse function if no transaction is found
        if (!$transaction) {
            return jsonResponse(false, "No approved transaction found for hiring request ID: $hiringRequestId.", [], 404);
        }

        // Return different responses based on who is authenticated (admin or API user)
        $message = $isAdmin
            ? "Admin: Approved transaction for hiring request ID: $hiringRequestId retrieved successfully."
            : "User: Approved transaction for hiring request ID: $hiringRequestId retrieved successfully.";

        return jsonResponse(true, $message, $transaction);
    }




}
