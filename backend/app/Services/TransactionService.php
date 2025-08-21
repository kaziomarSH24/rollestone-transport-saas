<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionService
{
    /**
     * Get the transaction history for a specific user based on their role.
     */
    public function getHistoryForUser(User $user, int $perPage = 15, ?string $filter = null): LengthAwarePaginator
    {
       $query = null;

        if ($user->hasRole('Passenger')) {
            $query = $user->transactions()->with(['journey.trip.route']);
        } elseif ($user->hasRole('Driver')) {
            $query = Transaction::where('processed_by_user_id', $user->id)
                                            ->with(['user', 'journey.trip.route']);
        } else {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        //Apply the type filter if it's provided.
        if ($filter) {
            match ($filter) {
                'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'this_month' => $query->whereMonth('created_at', now()->month),
                'refund' => $query->whereIn('type', ['Refund', 'CashRefund']),
                'topups' => $query->whereIn('type', ['TopUp', 'CashTopUp', 'StripeTopUp', 'AutoTopUp']),
                'trips' => $query->where('type', 'TripFare'),
                default => null, // 'All' filter does nothing.
            };
        }

        return $query->latest()->paginate($perPage);
    }
}
