<?php

namespace App\Services;


use App\Models\User;
use App\Services\BaseService;
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
            ]);
        });
    }
}
