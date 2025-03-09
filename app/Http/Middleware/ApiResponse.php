<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Skip ApiResponse middleware for the /files/{path} route
        if ($request->is('files/*')) {
            return $next($request);
        }

        // Capture the response
        $response = $next($request);

        // Check if the response is a valid Response object
        if ($response instanceof Response) {
            // Decode the response content if it's JSON
            $responseData = json_decode($response->getContent(), true) ?? [];

            // Initialize the formatted response structure
            $formattedResponse = [
                'data' => $responseData, // Default to the original response data
                'isError' => false,
                'error' => null,
                'status_code' => $response->status(),
            ];

            // Check if the response status indicates an error (>=400)
            if ($response->status() >= 400) {
                $formattedResponse['isError'] = true;

                // Extract the first error message from response data
                $errorMessage = $this->getFirstErrorMessage($responseData);

                // Set the error details in the response structure
                $formattedResponse['error'] = [
                    'code' => $response->status(),
                    'message' => Response::$statusTexts[$response->status()] ?? 'Unknown error',
                    'errMsg' => $errorMessage,
                ];

                // Clear the data field for error responses
                $formattedResponse['data'] = [];

                // Adjust status code if necessary
                $formattedResponse['status_code'] = $response->status();
            }

            // Return a 200 status code with the formatted response for consistency
            return response()->json($formattedResponse, 200);
        }

        // If the response is not an instance of Response, return it as is
        return $response;
    }

    /**
     * Extract the first error message from the response data.
     *
     * @param array $responseData
     * @return string
     */
    private function getFirstErrorMessage(array $responseData): string
    {
        // Check if the response contains a specific 'error' key
        if (isset($responseData['error'])) {
            return $responseData['error'];
        }

        // Check if the response contains Laravel validation errors
        if (isset($responseData['errors'])) {
            // Flatten the errors array and return the first error message
            $errors = array_values($responseData['errors']);
            return $errors[0][0] ?? 'An error occurred';
        }

        // Check if the response contains a 'message' key
        if (isset($responseData['message'])) {
            return $responseData['message'];
        }

        // Default error message
        return 'An error occurred';
    }
}
