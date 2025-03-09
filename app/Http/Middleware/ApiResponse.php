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
     * @param array|object $responseData
     * @return string
     */
    private function getFirstErrorMessage($responseData): string
    {
        // If the response data is an object, convert it to an array
        if (is_object($responseData)) {
            $responseData = (array) $responseData;
        }

        // If the response data is not an array, return a default error message
        if (!is_array($responseData)) {
            return 'An error occurred';
        }

        // Check if the response contains a specific 'error' key
        if (isset($responseData['error'])) {
            return is_array($responseData['error']) || is_object($responseData['error'])
                ? json_encode($responseData['error']) // Convert array/object to JSON string
                : (string) $responseData['error']; // Convert to string
        }

        // Check if the response contains Laravel validation errors
        if (isset($responseData['errors'])) {
            $errors = $responseData['errors'];

            // If errors is an array, flatten it and return the first error message
            if (is_array($errors)) {
                $errors = array_values($errors); // Reset array keys
                if (isset($errors[0])) {
                    if (is_array($errors[0]) || is_object($errors[0])) {
                        return json_encode($errors[0]); // Convert array/object to JSON string
                    }
                    return (string) $errors[0]; // Convert to string
                }
            }

            // If errors is a string or object, return it directly
            return is_array($errors) || is_object($errors)
                ? json_encode($errors) // Convert array/object to JSON string
                : (string) $errors; // Convert to string
        }

        // Check if the response contains a 'message' key
        if (isset($responseData['message'])) {
            return is_array($responseData['message']) || is_object($responseData['message'])
                ? json_encode($responseData['message']) // Convert array/object to JSON string
                : (string) $responseData['message']; // Convert to string
        }

        // Default error message
        return 'An error occurred';
    }
}
