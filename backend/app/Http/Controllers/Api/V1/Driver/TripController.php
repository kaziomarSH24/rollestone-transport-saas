<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Services\Driver\TripService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TripController extends Controller
{
    protected TripService $tripService;

    public function __construct(TripService $tripService)
    {
        $this->tripService = $tripService;
        // Middleware for authorization
        $this->middleware('role:Driver');
    }

    /**
     * Get available trips for a selected route.
     */
    public function getAvailableTrips(int $routeId)
    {
        $trips = $this->tripService->getAvailableTripsForRoute($routeId);
        if ($trips->isEmpty()) {
            return response_error('No available trips found for this route.', [], 404);
        }
        return response_success('Available trips retrieved.', $trips);
    }

    /**
     * Find an available trip by its number.
     */
    public function findByNumber(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_number' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }

        $trip = $this->tripService->findAvailableTripByNumber($request->trip_number);

        if (!$trip) {
            return response_error('Trip not found or is already active.', [], 404);
        }

        return response_success('Trip found successfully.', $trip);
    }
}
