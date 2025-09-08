<?php

namespace App\Services\Admin;

use App\Models\Journey;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{

    /**
     * Get the main statistics for the live dashboard cards.
     */
    public function getDashboardStats(): array
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $activeTrips = Journey::where('status', 'Ongoing')
            ->whereDate('journey_date', $today)
            ->count();


        $totalPassengersToday = Transaction::where('type', 'TripFare')
            ->whereDate('created_at', $today)
            ->get()
            ->sum(function ($transaction) {
                $details = json_decode($transaction->fare_details, true);
                return collect($details)->sum('quantity');
            });
        $totalPassengersYesterday = Transaction::where('type', 'TripFare')
            ->whereDate('created_at', $yesterday)
            ->get()
            ->sum(function ($transaction) {
                $details = json_decode($transaction->fare_details, true);
                return collect($details)->sum('quantity');
            });

        if ($totalPassengersYesterday == 0) {
            $passengerChange = $totalPassengersToday > 0 ? 100 : 0;
        } else {
            $passengerChange = (($totalPassengersToday - $totalPassengersYesterday) / $totalPassengersYesterday) * 100;
        }


        $revenueToday = Transaction::where('type', 'TripFare')
            ->whereDate('created_at', $today)
            ->sum(DB::raw('ABS(amount)'));



        $revenueYesterday = Transaction::where('type', 'TripFare')
            ->whereDate('created_at', $yesterday)
            ->sum(DB::raw('ABS(amount)'));

        if ($revenueYesterday == 0) {
            $percentageChange = $revenueToday > 0 ? 100 : 0;
        } else {
            $percentageChange = (($revenueToday - $revenueYesterday) / $revenueYesterday) * 100;
        }


        $activeDrivers = Journey::where('status', 'Ongoing')
            ->whereDate('journey_date', $today)
            ->distinct('driver_id')
            ->count('driver_id');

        return [
            'active_trips' => $activeTrips,
            'total_passengers' =>[
                'today' => $totalPassengersToday,
                'yesterday' => $totalPassengersYesterday,
                'percentage_change' => round($passengerChange, 2) . '%',
                'is_increase' => $passengerChange >= 0,
            ],
            'revenue' => [
                'today' => number_format($revenueToday),
                'yesterday' => number_format($revenueYesterday),
                'percentage_change' => round($percentageChange, 2) . '%',
                'is_increase' => $percentageChange >= 0,
            ],
            'active_drivers' => $activeDrivers,
        ];
    }
    /**
     * Get data for the live trip dashboard, including progress percentage.
     */
    public function getLiveDashboardData($filter = 'all')
    {
        // 23.817190217017178, 90.41037310255314
        // 23.773530466373803, 90.40149041636843
        // dd( getDistance(23.81719021, 90.41037310, 23.81082853, 90.40357080) );

        $query = Journey::query();
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $journeys = $query->whereDate('journey_date', Carbon::today())
            ->with(['trip.route.stops', 'driver.user'])
            ->withCount('transaction')
            ->get();

        $journeys->each(function ($journey) {
            switch ($journey->status) {
                case 'ongoing':
                    $journey->progress = $this->calculateProgressPercentage($journey);
                    $journey->passenger_count = $journey->transaction->sum(function ($transaction) {
                        $details = json_decode($transaction->fare_details, true);
                        return collect($details)->sum('quantity');
                    });
                    break;
                case 'completed':
                    $journey->progress = 100;
                    $journey->passenger_count = $journey->transaction_count;
                    break;
                default:
                    $journey->progress = 0;
                    break;
            }
        });
        return $journeys;
    }


    private function calculateProgressPercentage(Journey $journey): int
    {
        $stops = $journey->trip->route->stops;
        // dd($journey->current_lng);
        if ($stops->count() < 2 || !$journey->current_lat || !$journey->current_lng) {
            return 0;
        }

        // origin (1st stop)
        $firstStop = $stops->first();
        // Destination (last stop)
        $lastStop = $stops->last();


        // Driver’s current location
        $driverLat = $journey->current_lat;
        $driverLng = $journey->current_lng;

        // Total distance from origin to destination
        $totalDistance = getDistance(
            $firstStop->latitude,
            $firstStop->longitude,
            $lastStop->latitude,
            $lastStop->longitude
        );

        if ($totalDistance == 0) return 100;

        // Distance from driver's current location to destination
        $remainingDistance = getDistance(
            $driverLat,
            $driverLng,
            $lastStop->latitude,
            $lastStop->longitude
        );



        // Progress %
        $progress = (($totalDistance - $remainingDistance) / $totalDistance) * 100;

        return min(100, max(0, (int)$progress));
    }
}
