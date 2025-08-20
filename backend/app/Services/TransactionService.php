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
    public function getHistoryForUser(User $user, int $perPage = 15, ?string $filterType = null): LengthAwarePaginator
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
        if ($filterType) {
            $query->where('type', $filterType);
        }

        return $query->latest()->paginate($perPage);
    }
}
