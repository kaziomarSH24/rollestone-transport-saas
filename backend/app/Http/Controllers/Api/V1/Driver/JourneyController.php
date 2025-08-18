<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Driver\JourneyResource;
use App\Models\Journey;
use App\Services\Driver\JourneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JourneyController extends Controller
{
    protected JourneyService $journeyService;

    public function __construct(JourneyService $journeyService)
    {
        $this->journeyService = $journeyService;
        $this->middleware('role:Driver');
    }

    public function block(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'fleet_number' => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response_error($validator->errors()->first(),$validator->errors()->toArray(), 422);
        }
        try {
            $journey = $this->journeyService->blockTrip($request->trip_id, $request->fleet_number, $request->user());
            return response_success('Trip blocked successfully.', $journey);
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    public function start(Request $request, Journey $journey)
    {
        try {
            $journey = $this->journeyService->startJourney($journey, $request->user());

            return response_success('Journey started successfully.', $journey);
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    public function end(Request $request, Journey $journey)
    {
        try {
            $journey = $this->journeyService->endJourney($journey, $request->user());
            return response_success('Journey ended successfully.', $journey);
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    public function getDriverSchedule(Request $request)
    {
        $schedule = $this->journeyService->getDriverSchedule($request->user());
        if ($schedule->isEmpty()) {
            return response_error('No journeys found.', [], 404);
        }
        return JourneyResource::collection($schedule);
    }
}
