<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }


    /**
     * Get the user's transaction history.
     */
    public function getTransactionHistory(Request $request)
    {
        try {
            $perPage = $request->query('per_page', 15);
            $transactions = $request->user()->transactions()->latest()->paginate($perPage);
            return response_success('Transaction history retrieved.', $transactions);
        } catch (\Exception $e) {
            return response_error('Failed to retrieve transaction history: ' . $e->getMessage());
        }
    }

    public function createCardSetupSession(Request $request)
    {
        $checkoutSession = $this->paymentService->createCardSetupCheckoutSession($request->user());
        return response_success('Card setup session created.', ['setup_url' => $checkoutSession->url]);
    }


    //top up with saved card
    // public function topUpWithSavedCard(Request $request)
    // {
    //     try {
    //         $request->validate(['amount' => 'required|numeric|min:1']);
    //         $paymentIntent = $this->paymentService->chargeSavedCard($request->user(), $request->amount);
    //         return response_success('Payment initiated. Waiting for confirmation.', [
    //             'client_secret' => $paymentIntent->client_secret,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response_error('Payment failed: ' . $e->getMessage());
    //     }
    // }

    /**
     * API Endpoint for Option 2: Create a payment (and optionally save the card).
     */
    // public function createPaymentIntent(Request $request)
    // {
    //     $request->validate([
    //         'amount' => 'required|numeric|min:1',
    //         'save_card' => 'required|boolean',
    //     ]);

    //     $paymentIntent = $this->paymentService->createPaymentIntent(
    //         $request->user(),
    //         $request->amount,
    //         $request->boolean('save_card')
    //     );

    //     return response_success('Payment intent created.', ['client_secret' => $paymentIntent->client_secret]);
    // }

    /**
     * API Endpoint for Option 2: Create a payment link (and optionally save the card).
     */
    public function createPaymentSession(Request $request)
    {
        try {
            // $request->validate([
            //     'amount' => 'required|numeric|min:1',
            //     'auto_topup_enabled' => 'nullable|boolean',
            //     'use_default_card' => 'nullable|boolean',
            // ]);
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'auto_topup_enabled' => 'nullable|boolean',
                'use_default_card' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response_error($validator->errors()->first(), $validator->errors()->toArray());
            }

            $checkoutSession = $this->paymentService->createPaymentCheckoutSession(
                $request->user(),
                $request->amount,
                $request->boolean('auto_topup_enabled'),
                $request->boolean('use_default_card')
            );
            if ($checkoutSession->object === 'payment_intent') {
                return response_success('Payment initiated. Waiting for confirmation.', [
                    'client_secret' => $checkoutSession->client_secret,
                ]);
            }
            return response_success('Payment session created.', ['payment_url' => $checkoutSession->url]);
        } catch (\Exception $e) {
            return response_error('Payment failed: ' . $e->getMessage());
        }
    }



    public function requestRefund(Request $request)
    {
        // $request->validate(['charge_id' => 'required|string']);
        try{
            $validator = Validator::make($request->all(), [
            'charge_id' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response_error($validator->errors()->first(), $validator->errors()->toArray());
        }
        $this->paymentService->requestRefund($request->user(), $request->charge_id);
        return response_success('Refund request initiated. It will be processed shortly.');
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    //get passenger payment methods
    public function getPaymentMethods(Request $request)
    {
        try {
            $paymentMethods = $this->paymentService->getUserPaymentMethods($request->user());
            $paymentMethods->makeHidden([
                'fingerprint',
                'stripe_payment_method_id',
            ]);
            return response_success('Payment methods retrieved.', $paymentMethods);
        } catch (\Exception $e) {
            return response_error('Failed to retrieve payment methods: ' . $e->getMessage());
        }
    }
}
