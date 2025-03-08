<?php

namespace App\Http\Controllers\Api\Gateway\Stripe;

use Stripe\Stripe;
use App\Models\User;
use App\Models\Payment;
use App\Models\Profile;
use Illuminate\Http\Request;
use App\Models\HiringRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Stripe\Checkout\Session as StripeSession;

class StripePaymentController extends Controller
{
    /**
     * Create a Stripe Payment and save it to the database.
     */
    public function createPayment(Request $request)
    {
        $paymentData = [
            'userid' => 1,
            'amount' => 500,
            'applicant_mobile' => '1234567890',
            'balance' => 100
        ];

        return stripe($paymentData);
    }


    /**
     * Handle payment success (after redirect from Stripe).
     */
    public function paymentSuccess(Request $request)
    {
        // Retrieve the session ID from the URL
        $session_id = $request->input('session_id');

        // Find the payment by checkout_session_id
        $payment = Payment::where('checkout_session_id', $session_id)->first();

        // Check if the payment exists
        if (!$payment) {
            // Return error response if payment is not found
            return jsonResponse(false, 'Payment not found', null, 404);
        }

        // If the payment is already approved, no need to check with Stripe
        if ($payment->status === 'approved') {
            return jsonResponse(true, 'Payment is already approved', $payment, 200);
        }

        // If payment is not approved, check with Stripe
        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Retrieve session details from Stripe
            $session = \Stripe\Checkout\Session::retrieve($session_id);
        } catch (\Exception $e) {
            // Handle any errors from Stripe
            return jsonResponse(false, 'Error retrieving payment session from Stripe', null, 500);
        }

        // Use the private function to update payment status
        $this->updatePaymentStatus($payment, $session);

        // Return success response after updating payment status
        return jsonResponse(true, 'Payment status updated successfully', $payment, 200);
    }



    /**
     * Handle Stripe webhook notifications.
     */
    public function handleWebhook(Request $request)
    {
        // Set your Stripe secret key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Get the webhook secret from the environment variables
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        // Get the payload and signature header from the request
        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        // Log the payload for debugging purposes
        Log::info('Webhook Payload: ', ['payload' => $payload]);

        try {
            // Verify the event with the Stripe webhook secret
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            // Log the event type for debugging purposes
            Log::info('Stripe Event Type: ', ['event_type' => $event->type]);

            // Handle the 'checkout.session.completed' event
            if ($event->type == 'checkout.session.completed') {
                $session = $event->data->object;

                // Log the session data for debugging purposes
                Log::info('Session Object: ', ['session' => $session]);

                // Find the payment by the session's client reference ID (trxId)
                $payment = Payment::where('trxId', $session->client_reference_id)->first();

                // Check if the payment exists
                if ($payment) {
                    // Use the private function to update the payment status
                    $this->updatePaymentStatus($payment, $session);

                    // Log the successful processing of the payment
                    Log::info('Payment processed successfully: ', ['payment' => $payment]);
                } else {
                    // Log if the payment is not found
                    Log::warning('Payment not found for trxId: ' . $session->client_reference_id);
                }
            }
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            Log::error('Invalid Payload: ', ['error' => $e->getMessage()]);
            return jsonResponse(false, 'Invalid Payload', $e->getMessage(), 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            Log::error('Invalid Signature: ', ['error' => $e->getMessage()]);
            return jsonResponse(false, 'Invalid Signature', $e->getMessage(), 400);
        } catch (\Exception $e) {
            // General exception handling
            Log::error('Webhook Error: ', ['error' => $e->getMessage()]);
            return jsonResponse(false, 'Webhook Error', $e->getMessage(), 400);
        }

        // Return a success response to acknowledge receipt of the webhook
        return jsonResponse(true, 'Webhook received', null, 200);
    }


    /**
     * Private function to update the payment status.
     */
    private function updatePaymentStatus($payment, $session)
    {
        if ($session->payment_status == 'paid') {
            // Update payment to success
            $payment->update([
                'status' => 'approved',
                'ipnResponse' => json_encode($session),
            ]);

            // Check payment type and perform relevant actions
            if ($payment->type === 'activation') {
                // Call the activateUser function to activate the user
                $this->activateUser($payment->userid);
            } elseif ($payment->hiring_request_id) {
                // Check if payment has a related hiring request
                $hiringRequest = HiringRequest::find($payment->hiring_request_id);
                if ($hiringRequest) {
                    // Update the hiring request status to approved
                    $hiringRequest->update([
                        'status' => 'Pending', // Update the status as per your requirements
                    ]);
                }
            }
        } else {
            // Update payment to failed
            $payment->update([
                'status' => 'failed',
                'ipnResponse' => json_encode($session),
            ]);

            // Handle failure for activation type
            if ($payment->type === 'activation') {
                // Optionally, handle specific failure actions for activation payments
            } elseif ($payment->hiring_request_id) {
                // Check if payment has a related hiring request
                $hiringRequest = HiringRequest::find($payment->hiring_request_id);
                if ($hiringRequest) {
                    // Update the hiring request status to failed
                    $hiringRequest->update([
                        'status' => 'failed', // Update the status as per your requirements
                    ]);
                }
            }
        }
    }








    private function activateUser($userId)
    {
     
        // Find the profile by user ID
        $profile = Profile::where(['user_id'=> $userId,'profile_type'=>'employee'])->first();

        if ($profile) {
            // Check if the profile is already active
            if ($profile->status === 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'The user is already active.',
                ], 400);
            }

            // Activate the profile
            $profile->update([
                'status' => 'active',
                'step' => 3, // Assuming step 3 means the activation process is complete
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User activated successfully.',
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Profile not found for the given user.',
            ], 404);
        }
    }






}
