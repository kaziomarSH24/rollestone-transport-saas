<?php
namespace App\Console\Commands;

use App\Models\PassengerWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class ProcessAutoTopUps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autotopup:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process auto top-ups for passenger wallets with low balance';

    /**
     * Execute the console command.
     */
     public function handle()
    {
        $this->info('Starting to process auto top-ups...');
        // Stripe::setApiKey(config('services.stripe.secret'));

        // Find wallets where the balance is less than their specific threshold.
        $walletsToTopUp = PassengerWallet::where('auto_topup_enabled', true)
                                        ->whereColumn('balance', '<', 'auto_topup_threshold')
                                        ->with(['user.company', 'user.paymentMethods'])
                                        ->get();

        if ($walletsToTopUp->isEmpty()) {
            $this->info('No wallets found requiring auto top-up.');
            return 0;
        }

        $this->info($walletsToTopUp->count() . ' wallet(s) found for auto top-up.');

        foreach ($walletsToTopUp as $wallet) {
            $user = $wallet->user;
            $company = $user->company;
            $defaultPaymentMethod = $user->paymentMethods->where('is_default', true)->first();
            $topUpAmount = $wallet->auto_topup_amount; // Use the dynamic amount from the wallet

            if ($company && $company->stripe_secret_key && $user->stripe_customer_id && $defaultPaymentMethod && $topUpAmount > 0) {
                try {
                    // Set the API key for this specific company just before the API call.
                    Stripe::setApiKey($company->stripe_secret_key);

                    // Create a pending transaction record first.
                    $transaction = $user->transactions()->create([
                        'company_id' => $company->id, // Ensure company_id is set
                        'type' => 'AutoTopUp',
                        'amount' => $topUpAmount,
                        'status' => 'pending',
                        'payment_method' => 'Stripe', // Specify payment method
                    ]);

                    // Create the PaymentIntent. The database will be updated by the webhook.
                    $paymentIntent = PaymentIntent::create([
                        'customer' => $user->stripe_customer_id,
                        'payment_method' => $defaultPaymentMethod->stripe_payment_method_id,
                        'amount' => $topUpAmount * 100, // Amount in cents
                        'currency' => 'usd',
                        'off_session' => true,
                        'confirm' => true,
                        'return_url' => config('app.url'),
                        'metadata' => [
                            'transaction_id' => $transaction->id, // Pass our transaction ID
                        ],
                    ]);

                    // Update our transaction with the Stripe Payment Intent ID
                    $transaction->update(['stripe_payment_intent_id' => $paymentIntent->id]);

                    $this->info('PaymentIntent created for user ID: ' . $user->id . '. Waiting for webhook confirmation.');

                } catch (\Exception $e) {
                    $this->error('Auto top-up failed for user ID: ' . $user->id . '. Reason: ' . $e->getMessage());
                    Log::error('Auto top-up failed for user ' . $user->id . ': ' . $e->getMessage());
                }
            }
        }

        $this->info('Auto top-up process finished.');
        return 0;
    }
}
