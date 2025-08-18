<?php

namespace App\Services\Driver;

use App\Services\BaseService;
use App\Models\Journey;
use App\Models\User;
use Carbon\Carbon;

class JourneyService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Journey::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }

    /**
     * Blocks a trip for a driver for today.
     */
    public function blockTrip(int $tripId, string $fleetNumber, User $driverUser): Journey
    {
        $today = Carbon::today();
        if (Journey::where('trip_id', $tripId)->whereDate('journey_date', $today)->exists()) {
            throw new \Exception('This trip has already been blocked by another driver for today.');
        }

        // // Check if the driver already has an ongoing journey
        // if ($this->hasOngoingJourney($driverUser)) {
        //     throw new \Exception('You already have an ongoing journey. Please complete it first.');
        // }

        $data = [
            'trip_id' => $tripId,
            'driver_id' => $driverUser->driver->id,
            'fleet_number' => $fleetNumber,
            'journey_date' => $today,
            'status' => 'blocked',
        ];
        return $this->create($data);
    }

    /**
     * Starts a previously blocked journey.
     */
    public function startJourney(Journey $journey, User $driverUser): Journey
    {
        // Ensure the journey belongs to the current driver and is in a 'Blocked' state
        if ($journey->driver_id !== $driverUser->driver->id || $journey->status !== 'blocked') {
            throw new \Exception('This journey cannot be started.');
        }
        // Check if the driver already has an ongoing journey
        if ($this->hasOngoingJourney($driverUser)) {
            throw new \Exception('You already have another journey in progress.');
        }

        $journey->update([
            'status' => 'ongoing',
            'actual_departure_time' => Carbon::now(),
        ]);
        return $journey;
    }

    /**
     * Ends an ongoing journey.
     */
    public function endJourney(Journey $journey, User $driverUser): Journey
    {
        if ($journey->driver_id !== $driverUser->driver->id || $journey->status !== 'ongoing') {
            throw new \Exception('This journey cannot be ended.');
        }

        $journey->update([
            'status' => 'completed',
            'actual_arrival_time' => Carbon::now(),
        ]);
        return $journey;
    }

    /**
     * Get the schedule of journeys for the currently authenticated driver for today.
     */
    public function getDriverSchedule(User $driverUser): \Illuminate\Database\Eloquent\Collection
    {
        return Journey::where('driver_id', $driverUser->driver->id)
                      ->whereDate('journey_date', Carbon::today())
                      ->with(['trip.route.stops'])
                      ->latest()
                      ->get();
    }

    private function hasOngoingJourney(User $driverUser): bool
    {
        return Journey::where('driver_id', $driverUser->driver->id)
                      ->where('status', 'ongoing')
                      ->exists();
    }
}

