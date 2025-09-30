<?php
// app/Services/PaymentService.php
namespace App\Services;

use App\Models\User;
use Exception;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Refund;
use Stripe\PaymentIntent;

class PaymentService
{
    /**
     * Helper function to set the Stripe key for a specific user's company.
     */
    private function setStripeKeyForUser(User $user): void
    {
        $company = $user->company;

        if (!$company || !$company->stripe_secret_key) {
            throw new Exception('Stripe payment is not configured for this company.');
        }

        Stripe::setApiKey($company->stripe_secret_key);
    }


    public function getOrCreateStripeCustomer(User $user): Customer
    {
        $this->setStripeKeyForUser($user);

        if ($user->stripe_customer_id) {
            return Customer::retrieve($user->stripe_customer_id);
        }
        $customer = Customer::create(['email' => $user->email, 'name' => $user->name]);
        $user->update(['stripe_customer_id' => $customer->id]);
        return $customer;
    }

    public function createCardSetupCheckoutSession(User $user, $successUrl, $cancelUrl): CheckoutSession
    {
        $this->setStripeKeyForUser($user);
        $customer = $this->getOrCreateStripeCustomer($user);
        return CheckoutSession::create([
            'customer' => $customer->id,
            'mode' => 'setup',
            'payment_method_types' => ['card'],
            'success_url' => $successUrl ?? 'https://yourapp.com/payment-method/success',
            'cancel_url' => $cancelUrl ?? 'https://yourapp.com/payment-method/cancel',
        ]);
    }

    /**
     * Charges a user's saved card for a manual top-up using Payment Intents.
     */
    public function chargeSavedCard(User $user, float $amount): PaymentIntent
    {
        $this->setStripeKeyForUser($user);
        $customer = $this->getOrCreateStripeCustomer($user);
        $defaultPaymentMethod = $user->paymentMethods()->where('is_default', true)->first();

        if (!$defaultPaymentMethod) {
            throw new \Exception('No default payment method found.');
        }

        $amountInCents = round($amount * 100);

        // First, create a pending transaction record in database.
        $transaction = $user->transactions()->create([
            'company_id' => $user->company_id,
            'type' => 'TopUp',
            'amount' => $amount,
            'status' => 'pending',
            'payment_method' => 'Stripe',
        ]);

        $paymentIntent = PaymentIntent::create([
            'customer' => $customer->id,
            'payment_method' => $defaultPaymentMethod->stripe_payment_method_id,
            'amount' => $amountInCents,
            'currency' => 'usd',
            'off_session' => false,
            'confirm' => true,
            'metadata' => [
                'transaction_id' => $transaction->id, // Link to the transaction
            ],
            'return_url' => config('app.url'),
        ]);
        // Save the Stripe Payment Intent ID to our transaction record
        $transaction->update(['stripe_payment_intent_id' => $paymentIntent->id]);

        return $paymentIntent;
    }
    
    /**
     * Creates a Stripe Checkout Session for wallet top-up.
     * If $defaultPaymentMethod is true, it charges the saved card directly instead.
     */
    public function createPaymentCheckoutSession(User $user, float $amount, bool $saveCard = false, $defaultPaymentMethod = false): CheckoutSession | PaymentIntent
    {
        $this->setStripeKeyForUser($user);
        if ($defaultPaymentMethod) {
            $paymentIntent = $this->chargeSavedCard($user, $amount);
            return $paymentIntent;
        }
        $customer = $this->getOrCreateStripeCustomer($user);
        $amountInCents = round($amount * 100);

        $transaction = $user->transactions()->create([
            'company_id' => $user->company_id,
            'type' => 'TopUp',
            'payment_method' => 'Stripe',
            'amount' => $amount,
            'status' => 'pending',
        ]);

        $checkoutSessionParams = [
            'customer' => $customer->id,
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => ['name' => 'Wallet Top-up'],
                    'unit_amount' => $amountInCents,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'transaction_id' => $transaction->id,
            ],
            'success_url' => env('STRIPE_PAYMENT_SUCCESS_URL', 'https://yourapp.com/payment/success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => env('STRIPE_PAYMENT_CANCEL_URL', 'https://yourapp.com/payment/cancel')
        ];

        // If saving the card, set up future usage for automatic top-ups
        if ($saveCard) {
            $checkoutSessionParams['payment_intent_data'] = [
                'setup_future_usage' => 'off_session',
            ];
        }

        $session = CheckoutSession::create($checkoutSessionParams);
        return $session;
    }





    public function requestRefund(User $user, string $stripeChargeId): void
    {

        try {
            $this->setStripeKeyForUser($user);
            $originalTransaction = $user->transactions()
                ->where('stripe_charge_id', $stripeChargeId)
                ->where('type', 'TopUp')
                ->firstOrFail();

            // Create a pending refund transaction record
            $user->transactions()->create([
                'company_id' => $user->company_id,
                'type' => 'Refund',
                'amount' => -$originalTransaction->amount, // Store refunds as negative
                'status' => 'pending',
                'stripe_charge_id' => $stripeChargeId,
                'payment_method' => 'Stripe',
            ]);

            // Initiate the refund with Stripe
            Refund::create(['charge' => $stripeChargeId]);
        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }

    //save card information
    // public function savePaymentMethodFromWebhook(string $customerId, string $stripePaymentMethodId): void
    // {
    //     $user = User::where('stripe_customer_id', $customerId)->first();
    //     if (!$user) {
    //         // If user is not found, we can't save the card.
    //         return;
    //     }

    //     $stripePaymentMethod = PaymentMethod::retrieve($stripePaymentMethodId);

    //     // Set any other existing cards for this user to not be the default.
    //     $user->paymentMethods()->update(['is_default' => false]);

    //     // Save the new card details to our database and mark it as the default.
    //     $user->paymentMethods()->create([
    //         'stripe_payment_method_id' => $stripePaymentMethod->id,
    //         'card_brand' => $stripePaymentMethod->card->brand,
    //         'last_four' => $stripePaymentMethod->card->last4,
    //         'is_default' => true,
    //     ]);
    // }

    /**
     * Saves card details in our database. This is called by the webhook.
     */
    public function savePaymentMethod(User $user, string $stripePaymentMethodId): void
    {
        $this->setStripeKeyForUser($user);
        $stripePaymentMethod = PaymentMethod::retrieve($stripePaymentMethodId);
        $fingerprint = $stripePaymentMethod->card->fingerprint;

        // Set any other existing cards for this user to not be the default.
        $user->paymentMethods()->update(['is_default' => false]);

        $paymentMethod = $user->paymentMethods()->firstOrCreate(
            ['fingerprint' => $fingerprint],
            [
                'stripe_payment_method_id' => $stripePaymentMethod->id,
                'card_brand' => $stripePaymentMethod->card->brand,
                'last_four' => $stripePaymentMethod->card->last4,
                'exp_month' => $stripePaymentMethod->card->exp_month,
                'exp_year' => $stripePaymentMethod->card->exp_year,
            ]
        );

        // Mark this card as the default.
        $paymentMethod->update(['is_default' => true]);
    }

    //get user payment methods
    public function getUserPaymentMethods(User $user)
    {
        return $user->paymentMethods()->get();
    }

    //set another card as default
    public function setDefaultPaymentMethod(User $user, string $paymentMethodId): void
    {
        $paymentMethod = $user->paymentMethods()->where('id', $paymentMethodId)->first();
        if (!$paymentMethod) {
            throw new \Exception('Payment method not found.');
        }
        // Set all other cards to not be default
        $user->paymentMethods()->update(['is_default' => false]);
        // Set the selected card as default
        $paymentMethod->update(['is_default' => true]);
    }

    //remove user payment method
    public function removePaymentMethod(User $user, string $paymentMethodId): void
    {
        $paymentMethod = $user->paymentMethods()->where('id', $paymentMethodId)->first();
        if (!$paymentMethod) {
            throw new \Exception('Payment method not found.');
        }
        $this->setStripeKeyForUser($user);
        // Detach from Stripe
        $stripePaymentMethod = PaymentMethod::retrieve($paymentMethod->stripe_payment_method_id);
        $stripePaymentMethod->detach();
        // Remove from our database
        $paymentMethod->delete();

        // If the removed card was the default, set another card as default if available
        if ($paymentMethod->is_default) {
            $newDefault = $user->paymentMethods()->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }

        }
    }
}
