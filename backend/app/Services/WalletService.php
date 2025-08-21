<?php

namespace App\Services;
use App\Models\User;
use App\Notifications\ManualRefundNotification;
use App\Notifications\ManualTopUpNotification;
use Illuminate\Support\Facades\DB;

class WalletService
{

    /**
     * Manually adds funds to a passenger's wallet.
     */
    public function manualTopUp(User $passenger, float $amount, User $staff, string $paymentMethod = 'Cash'): void
    {
        DB::transaction(function () use ($passenger, $amount, $staff, $paymentMethod) {
            //Update the passenger's wallet balance
            $passenger->wallet()->increment('balance', $amount);

            //Create a transaction record for history
            $passenger->transactions()->create([
                'type' => 'CashTopUp',
                'amount' => $amount,
                'status' => 'succeeded',
                'processed_by_user_id' => $staff->id,
                'payment_method' => $paymentMethod,
            ]);
            // Notify the passenger about the top-up
            $passenger->notify(new ManualTopUpNotification($amount, $staff));
        });
    }

    /**
     * Manually refunds funds from a passenger's wallet.
     */
    public function manualRefund(User $passenger, float $amount, User $staff): void
    {
        if ($passenger->wallet->balance < $amount) {
            throw new \Exception('Insufficient balance for refund.');
        }

        DB::transaction(function () use ($passenger, $amount, $staff) {
            $passenger->wallet()->decrement('balance', $amount);

            $passenger->transactions()->create([
                'type' => 'CashRefund',
                'amount' => -$amount, // Refunds are stored as negative
                'status' => 'succeeded',
                'processed_by_user_id' => $staff->id,
                'payment_method' => 'Cash',
            ]);

            // Notify the passenger about the refund
            $passenger->notify(new ManualRefundNotification($amount, $staff));
        });
    }
}
