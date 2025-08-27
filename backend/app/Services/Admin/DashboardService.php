<?php

namespace App\Services\Admin;

use App\Models\Journey;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Get data for the live trip dashboard, including progress percentage.
     */
    public function getLiveDashboardData()
    {
        // 23.817190217017178, 90.41037310255314
        // 23.773530466373803, 90.40149041636843
        // dd( getDistance(23.81719021, 90.41037310, 23.81082853, 90.40357080) );

        $journeys = Journey::whereDate('journey_date', Carbon::today())
            ->with(['trip.route.stops', 'driver.user'])
            ->get();


        $journeys->each(function ($journey) {
            $journey->progress = $this->calculateProgressPercentage($journey) ?? 0;
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
