<?php

namespace App\Http\Controllers\Api\V1\Driver;

use App\Http\Controllers\Controller;
use App\Http\Resources\Driver\JourneyResource;
use App\Models\Journey;
use App\Services\Driver\JourneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
            return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
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

            return response_success('Trip started successfully.', $journey);
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    public function end(Request $request, Journey $journey)
    {
        try {
            $journey = $this->journeyService->endJourney($journey, $request->user());
            return response_success('Trip ended successfully.', $journey);
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    public function getDriverSchedule(Request $request)
    {
        $schedule = $this->journeyService->getDriverSchedule($request->user());
        if ($schedule->isEmpty()) {
            return response_error('No Trips found.', [], 404);
        }
        return JourneyResource::collection($schedule);
    }

    /**
     * Process payment from a passenger's QR code or as a cash transaction.
     */
    public function processPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_number' => 'required_if:payment_method,Wallet|nullable|string|exists:users,qr_code_number',
            'payment_method' => 'required|string|in:Wallet,Cash',
            'fares' => 'required|array',
            'fares.*.type' => 'required|string',
            'fares.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }
        $validator = $validator->validated();

        try {
            $totalCharged = $this->journeyService->processGroupPayment(
                $request->user(), // Authenticated driver
                $validator['payment_method'],
                $validator['fares'],
                $validator['qr_number'] ?? null
            );
            return response_success('Payment successful.', ['total_charged' => $totalCharged]);
        } catch (ValidationException $e) {
            return response_error('Payment failed', $e->errors());
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }

    // Process a simple "Scan & Go" payment for a single passenger.
    public function processSingleUserPayment(Request $request)
    {
        $validated = $request->validate([
            'qr_number' => 'required|string|exists:users,qr_code_number',
        ]);

        try {
            $result = $this->journeyService->processSingleUserPayment(
                $request->user(), // Authenticated driver
                $validated['qr_number']
            );
            return response_success('Payment successful.', $result);
        } catch (ValidationException $e) {
            return response_error('Payment failed', $e->errors());
        } catch (\Exception $e) {
            return response_error($e->getMessage());
        }
    }
}
