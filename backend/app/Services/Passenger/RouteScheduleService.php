<?php

namespace App\Services\Passenger;

use App\Models\Route;
use Carbon\Carbon;

class RouteScheduleService
{
    /**
     * Generates separate stop-centric schedules for outbound and inbound directions of a route.
     */
    public function generateStopCentricSchedule(int $routeId): array
    {
        $route = Route::with([
            'fares',
            'stops' => fn($q) => $q->orderBy('stop_order'),
            'trips' => fn($q) => $q->where('is_active', true)->orderBy('departure_time')
        ])->findOrFail($routeId);


        $tripsByDirection = $route->trips->groupBy('direction');

        $outboundTrips = $tripsByDirection->get('outbound', collect());
        $inboundTrips = $tripsByDirection->get('inbound', collect());

        $outboundStops = $this->calculateStopTimes($route->stops, $outboundTrips);

        $reversedStops = $this->reverseStops($route->stops);
        $inboundStops = $this->calculateStopTimes($reversedStops, $inboundTrips);

        $firstStopName = $route->stops->first()->location_name ?? 'Start';
        $lastStopName = $route->stops->last()->location_name ?? 'End';
        $firstStopLatitude = $route->stops->first()->latitude ?? null;
        $firstStopLongitude = $route->stops->first()->longitude ?? null;
        $lastStopLatitude = $route->stops->last()->latitude ?? null;
        $lastStopLongitude = $route->stops->last()->longitude ?? null;


        return [
            'route_id' => $route->id,
            'route_name' => $route->name,
            'start_latitude' => $firstStopLatitude,
            'start_longitude' => $firstStopLongitude,
            'end_latitude' => $lastStopLatitude,
            'end_longitude' => $lastStopLongitude,
            'outbound_schedule' => [
                'direction_name' => "{$firstStopName} to {$lastStopName}",
                'stops' => $outboundStops,
            ],
            'inbound_schedule' => [
                'direction_name' => "{$lastStopName} to {$firstStopName}",
                'stops' => $inboundStops,
            ],
        ];
    }

    /**
     * Helper function to calculate arrival times for a given set of stops and trips.
     */
    private function calculateStopTimes($stops, $trips): array
    {
        $stopsData = [];
        foreach ($stops as $stop) {
            $tripTimes = [];
            foreach ($trips as $trip) {
                $departureTime = Carbon::parse($trip->departure_time);
                // Use the correct minutes_from_start for the stop object or array
                $minutes = is_array($stop) ? $stop['minutes_from_start'] : $stop->minutes_from_start;
                $arrivalTime = $departureTime->copy()->addMinutes($minutes);
                $tripTimes[] = $arrivalTime->format('H:i');
            }
            $stopsData[] = [
                'stop_name' => is_array($stop) ? $stop['location_name'] : $stop->location_name,
                'times' => $tripTimes,
                'latitude' => is_array($stop) ? null : $stop->latitude,
                'longitude' => is_array($stop) ? null : $stop->longitude,
            ];
        }
        return $stopsData;
    }

    /**
     * Reverses the order of stops and recalculates their timings for inbound trips.
     */
    private function reverseStops($stops)
    {
        if ($stops->isEmpty()) {
            return collect();
        }

        $reversedStops = $stops->reverse()->values();
        $totalDuration = $stops->max('minutes_from_start');

        return $reversedStops->map(function ($stop) use ($totalDuration) {
            return [
                'location_name' => $stop->location_name,
                'minutes_from_start' => $totalDuration - $stop->minutes_from_start,
            ];
        });
    }
}
