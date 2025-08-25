<?php

namespace App\Services\Admin;

use App\Models\CashReconciliation;
use App\Models\Driver;
use App\Models\Route;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportingService
{

    public function getRevenueByRoute(string $filter = 'monthly')
    {
        $startDate = match ($filter) {
            'daily' => Carbon::today()->startOfDay(),
            'weekly' => Carbon::now()->startOfWeek(),
            default => Carbon::now()->startOfMonth(),
        };
        $endDate = Carbon::now()->endOfDay();
        return Transaction::query()
            ->select(
                'routes.name as route_name',
                'routes.route_prefix',
                DB::raw('SUM(ABS(transactions.amount)) as total_revenue')
            )
            ->join('journeys', 'transactions.journey_id', '=', 'journeys.id')
            ->join('trips', 'journeys.trip_id', '=', 'trips.id')
            ->join('routes', 'trips.route_id', '=', 'routes.id')
            ->where('transactions.type', 'TripFare')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('routes.id', 'routes.name', 'routes.route_prefix')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    //Get monthly revenue trends
    public function getMonthlyRevenueTrends()
    {
        return Transaction::query()
            ->select(
                DB::raw('MONTHNAME(created_at) as month_name'),
                DB::raw('SUM(ABS(amount)) as total_revenue'),
                DB::raw('MONTH(created_at) as month_number')
            )
            ->where('type', 'TripFare')
            ->whereYear('created_at', Carbon::now()->year) //only current year data
            ->groupBy('month_name', 'month_number')
            ->orderBy('month_number', 'asc') // order by month
            ->get();
    }

    /**
     * Get the data for the cash reconciliation table for a specific date.
     */
    public function getCashReconciliationData(string $date): \Illuminate\Support\Collection
    {
        $targetDate = Carbon::parse($date);

        //  Fetch total cash and wallet collections grouped by driver for the given date
        $collections = Transaction::query()
            ->select(
                'drivers.id as driver_id',
                DB::raw('SUM(CASE WHEN transactions.payment_method = "Cash" THEN ABS(transactions.amount) ELSE 0 END) as total_cash'),
                DB::raw('SUM(CASE WHEN transactions.payment_method = "Wallet" THEN ABS(transactions.amount) ELSE 0 END) as total_wallet')
            )
            ->join('users', 'transactions.processed_by_user_id', '=', 'users.id')
            ->join('drivers', 'users.id', '=', 'drivers.user_id')
            ->where('transactions.type', 'TripFare')
            ->whereDate('transactions.created_at', $targetDate)
            ->groupBy('drivers.id')
            ->get()
            ->keyBy('driver_id');

        // 2. Fetch previously saved reconciliation data for the given date
        $reconciledData = CashReconciliation::where('date', $targetDate)
            ->get()
            ->keyBy('driver_id');

        // all drivers for the company
        $allDrivers = Driver::with('user:id,name')->get();

        // merge for final output
        return $allDrivers->map(function ($driver) use ($collections, $reconciledData, $targetDate) {
            $collection = $collections->get($driver->id);
            $reconciled = $reconciledData->get($driver->id);

            return [
                'date' => $targetDate->toDateString(),
                'driver_id' => $driver->id,
                'driver_name' => $driver->user->name,
                'cash_processed' => $collection ? $collection->total_cash : 0.00,
                'wallet_collection' => $collection ? $collection->total_wallet : 0.00,
                'received_cash' => $reconciled ? $reconciled->received_cash : null,
                'difference' => $reconciled ? $reconciled->difference : null,
                'is_checked' => !is_null($reconciled),
            ];
        });
    }

    /**
     * Saves or updates a cash reconciliation record.
     */
    public function checkCashReconciliation(array $data): CashReconciliation
    {
        $cashProcessed = $data['cash_processed'];
        $receivedCash = $data['received_cash'];
        $difference = $receivedCash - $cashProcessed;

        return CashReconciliation::updateOrCreate(
            [
                'driver_id' => $data['driver_id'],
                'date' => $data['date'],
            ],
            [
                'system_cash' => $cashProcessed,
                'received_cash' => $receivedCash,
                'difference' => $difference,
                'checked_by_user_id' => auth()->id(),
            ]
        );
    }

    // Get passenger analytics
    public function getPassengerAnalytics(): array
    {

        $totalPassengers = User::role('Passenger')->count();

        $dailyActivePassengers = Transaction::where('type', 'TripFare')
            ->whereDate('created_at', Carbon::today())
            ->distinct('user_id')
            ->count('user_id');
        return [
            'total_passengers' => $totalPassengers,
            'daily_active_passengers' => $dailyActivePassengers,
        ];
    }

    /**
     * Get key statistics for all routes.
     */
    public function getRouteStatistics()
    {
        $routes = Route::with(['trips', 'journeys'])->get();
        // dd($routes);


        return $routes->map(function ($route) {

            $dailyTrips = $route->trips->count();

            $revenue = Transaction::whereHas('journey.trip', function ($query) use ($route) {
                $query->where('route_id', $route->id);
            })->where('type', 'TripFare')->sum(DB::raw('ABS(amount)'));


            $passengers = Transaction::whereHas('journey.trip', function ($query) use ($route) {
                $query->where('route_id', $route->id);
            })->where('type', 'TripFare')->get()->sum(function ($transaction) {
                $details = json_decode($transaction->fare_details, true);
                return collect($details)->sum('quantity');
            });
            $totalJourneys = $route->journeys->count();
            $onTimeJourneys = $route->journeys->filter(function ($journey) {
                return $journey->actual_departure_time <= $journey->journey_date . ' ' . $journey->trip->departure_time;
            })->count();

            $onTimeRate = ($totalJourneys > 0) ? round(($onTimeJourneys / $totalJourneys) * 100) : 100;

            return [
                'route_name' => $route->name,
                'daily_trips' => $dailyTrips,
                'passengers' => $passengers,
                'revenue' => number_format($revenue, 2),
                'on_time_rate' => $onTimeRate . '%',
            ];
        });
    }
}
