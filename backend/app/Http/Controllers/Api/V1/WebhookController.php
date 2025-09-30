<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Company;
use App\Notifications\PaymentSuccessNotification;
use App\Notifications\RefundSuccessNotification;
use App\Services\PaymentService;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\PaymentIntent;
use Stripe\Refund;

class WebhookController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Handle incoming Stripe webhooks.
     */
    public function handleStripeWebhook(Request $request)
    {
        $company = $this->findCompanyFromWebhook($request);
        if (!$company || !$company->stripe_webhook_secret) {
            Log::error('Stripe webhook called without a valid company or webhook secret.');
        }

        $payload = @file_get_contents('php://input');
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = $company->stripe_webhook_secret; // Use the company's webhook secret
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                if ($session->mode === 'setup') {
                    $this->handleSuccessfulCardSetup($session, $company);
                }
                if ($session->mode === 'payment' && $session->payment_status === 'paid') {
                    $this->handleSuccessfulPaymentSession($session, $company);
                }
                break;

            //Handle successful payments
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handleSuccessfulPayment($paymentIntent);
                break;
            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handleFailedPayment($paymentIntent);
                break;
            case 'charge.refund.updated':
                $refund = $event->data->object; // This is a Refund object
                if ($refund->status === 'succeeded') {
                    $this->handleSuccessfulRefund($refund);
                }
                break;

            default:
                // Unexpected event type
        }


        return response()->json(['status' => 'success']);
    }

    /**
     * Finds the company associated with a Stripe webhook event.
     */
    private function findCompanyFromWebhook(Request $request): ?Company
    {
        $payload = json_decode($request->getContent());
        $stripeCustomerId = $payload->data->object->customer ?? null;

        if (!$stripeCustomerId) {
            return null;
        }

        $user = User::where('stripe_customer_id', $stripeCustomerId)->first();

        return $user?->company;
    }


    // Link the transaction to the Payment Intent ID
    protected function linkTransactionToPaymentIntent($session)
    {
        $transactionId = $session->metadata->transaction_id ?? null;
        $transaction = Transaction::find($transactionId);

        if ($transaction && !$transaction->stripe_payment_intent_id) {
            $transaction->update([
                'stripe_payment_intent_id' => $session->payment_intent,
            ]);
        }
    }

    protected function handleSuccessfulCardSetup($session, Company $company)
    {
        if (!$company->stripe_secret_key) {
            Log::error('Stripe secret key is not set for the current company.');
            return;
        }
        $setupIntentId = $session->setup_intent;
        $stripe = new StripeClient($company->stripe_secret_key);
        $setupIntent = $stripe->setupIntents->retrieve($setupIntentId);

        $user = User::where('stripe_customer_id', $setupIntent->customer)->first();
        if ($user) {
            $this->paymentService->savePaymentMethod($user, $setupIntent->payment_method);
        }
    }



    protected function handleSuccessfulPaymentSession($session, Company $company)
    {
        if (!$company->stripe_secret_key) {
            Log::error('Stripe secret key is not set for the current company.');
            return;
        }
        $transactionId = $session->metadata->transaction_id ?? null;
        $transaction = Transaction::find($transactionId);

        if ($transaction && $transaction->status === 'pending') {
            $user = $transaction->user;
            $stripe = new StripeClient($company->stripe_secret_key);
            // Retrieve the full Payment Intent to get the charge ID
            $paymentIntent = $stripe->paymentIntents->retrieve($session->payment_intent);
            // Update transaction status and save the Payment Intent ID
            $transaction->update([
                'status' => 'succeeded',
                'stripe_payment_intent_id' => $session->payment_intent,
                'stripe_charge_id' => $paymentIntent->latest_charge, // Assuming the charge ID is the same as the Payment Intent ID
            ]);

            // Update user's wallet balance
            $user->wallet->increment('balance', $transaction->amount);

            // Check if the card needs to be saved for future use
            $paymentIntent = $stripe->paymentIntents->retrieve($session->payment_intent);
            if ($paymentIntent->setup_future_usage) {
                $this->paymentService->savePaymentMethod($user, $paymentIntent->payment_method);

                //update wallet auto top up settings
                if (!$user->wallet->auto_topup_enabled) {
                    $user->wallet->update([
                        'auto_topup_enabled' => true,
                    ]);
                }
            }
            $user->notify(new PaymentSuccessNotification($paymentIntent));
        }
    }



    protected function handleSuccessfulPayment(PaymentIntent $paymentIntent)
    {
        // Find the transaction using the reliable Payment Intent ID,
        // which we saved when the payment was initiated.

        $transaction = Transaction::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        if ($transaction && $transaction->status === 'pending') {
            $user = $transaction->user;
            $transaction->update([
                'status' => 'succeeded',
                'stripe_charge_id' => $paymentIntent->latest_charge,
            ]);
            $user->wallet->increment('balance', $transaction->amount);

            if ($paymentIntent->setup_future_usage) {
                $this->paymentService->savePaymentMethod($user, $paymentIntent->payment_method);
            }

            $user->notify(new PaymentSuccessNotification($paymentIntent));
        }
    }


    protected function handleFailedPayment(PaymentIntent $paymentIntent)
    {
        $transactionId = $paymentIntent->metadata->transaction_id ?? null;
        $transaction = Transaction::find($transactionId);

        if ($transaction && $transaction->status === 'pending') {
            $transaction->update(['status' => 'failed']);
        }
    }

    //refund
    protected function handleSuccessfulRefund(\Stripe\Refund $refund)
    {
        // Find our internal pending refund transaction using the charge ID from the refund object
        $transaction = Transaction::where('stripe_charge_id', $refund->charge)
            ->where('status', 'pending')
            ->where('type', 'Refund')
            ->first();

        if ($transaction) {
            $user = $transaction->user;

            // Update transaction status to 'succeeded'
            $transaction->update(['status' => 'succeeded']);

            // Update user's wallet balance
            $user->wallet->decrement('balance', abs($transaction->amount));
            $user->notify(new RefundSuccessNotification($refund));
        }
    }
}
