<?php

namespace App\Services\Driver;

use App\Models\Journey;
use App\Services\BaseService;
use App\Models\Trip;
use Carbon\Carbon;

class TripService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Trip::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }

    /**
     * Find available (not yet started as a journey) trips for a specific route for today.
     */
    public function getAvailableTripsForRoute(int $routeId)
    {
        $today = Carbon::today();

        // Get all trips for the given route for today
        $startedTripIds =Journey::whereDate('journey_date', $today)
            ->pluck('trip_id');

        // Find trips for the given route for today that are NOT in the started list
        return $this->modelClass::with('route.stops')->where('route_id', $routeId)
            // ->where('trip_date', $today)
            ->whereNotIn('id', $startedTripIds)
            ->where('is_active', true)
            ->orderBy('departure_time')
            ->get();
    }

    /**
     * Find an available trip by its unique trip number for today.
     */
    public function findAvailableTripByNumber(string $tripNumber)
    {
        $today = Carbon::today();
        $startedTripIds = Journey::whereDate('journey_date', $today)->pluck('trip_id');
        return Trip::where('trip_number', $tripNumber)
                   ->whereNotIn('id', $startedTripIds)
                   ->where('is_active', true)
                   ->first();
    }
}
