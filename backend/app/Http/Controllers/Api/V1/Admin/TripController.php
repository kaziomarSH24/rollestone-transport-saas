<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TripStoreRequest;
use App\Services\Admin\TripScheduleService;
use App\Services\TripService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    protected TripService $tripService;
    protected TripScheduleService $tripScheduleService;
    public function __construct(TripService $tripService, TripScheduleService $tripScheduleService)
    {
        $this->tripService = $tripService;
        $this->tripScheduleService = $tripScheduleService;
        //middleware for authorization
        $this->middleware('can:view trips')->only(['index', 'show']);
        $this->middleware('can:create trips')->only(['store']);
        $this->middleware('can:edit trips')->only(['update']);
        $this->middleware('can:delete trips')->only(['destroy']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $trips = $this->tripService->getAll();
            return response_success('Trips retrieved successfully.', $trips);
        } catch (\Exception $e) {
            return response_error('Failed to retrieve trips: ' . $e->getMessage(), [], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(TripStoreRequest $request)
    {
        try{
            $trip = $this->tripService->create($request->validated());
            return response_success('Trip created successfully.', $trip, 201);
        } catch (\Exception $e) {
            return response_error('Failed to create trip: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $trip = $this->tripScheduleService->getCalculatedScheduleForTrip((int)$id);
        if (!$trip) {
            return response_error('Trip not found.', [], 404);
        }
        return response_success('Trip retrieved successfully.', $trip);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TripStoreRequest $request, string $id)
    {
        try {
            $trip = $this->tripService->update((int)$id, $request->validated());
            return response_success('Trip updated successfully.', $trip);
        } catch (\Exception $e) {
            return response_error('Failed to update trip: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->tripService->delete((int)$id);
        return response_success('Trip deleted successfully.');
    }
}
