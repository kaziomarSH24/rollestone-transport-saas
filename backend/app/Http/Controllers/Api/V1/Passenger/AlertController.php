<?php

namespace App\Http\Controllers\Api\V1\Passenger;

use App\Http\Controllers\Controller;
use App\Services\Passenger\AlertService;
use Illuminate\Http\Request;
use App\Models\Route;
use App\Models\Trip;
use Illuminate\Support\Facades\Validator;

class AlertController extends Controller
{
    protected AlertService $alertService;
    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    //get all trip alert times for a passenger
    public function index(Request $request)
    {
        try {
            $passenger = $request->user();

            // Get user's active alerts
            $userAlerts = $passenger->tripAlerts()->pluck('trip_id')->toArray();

            // Get routes with their trips grouped by direction
            $routes = Route::with(['trips' => function ($query) {
                $query->where('is_active', 1)->orderBy('departure_time');
            }])->get();

            $data = $routes->map(function ($route) use ($userAlerts) {
                // Group trips by direction for this route
                $inboundTrips = $route->trips->where('direction', 'inbound');
                $outboundTrips = $route->trips->where('direction', 'outbound');

                // Extract and format times with alert status
                $inboundTimes = $inboundTrips->map(function ($trip) use ($userAlerts) {
                    return [
                        'trip_id' => $trip->id,
                        'time' => date('g:i A', strtotime($trip->departure_time)),
                        'is_alert_active' => in_array($trip->id, $userAlerts)
                    ];
                })->values();

                $outboundTimes = $outboundTrips->map(function ($trip) use ($userAlerts) {
                    return [
                        'trip_id' => $trip->id,
                        'time' => date('g:i A', strtotime($trip->departure_time)),
                        'is_alert_active' => in_array($trip->id, $userAlerts)
                    ];
                })->values();

                return [
                    'route_id' => $route->id,
                    'route_name' => $route->name,
                    'inbound_times' => $inboundTimes,
                    'outbound_times' => $outboundTimes,
                ];
            })->filter(function ($route) {
                return $route['inbound_times']->count() > 0 || $route['outbound_times']->count() > 0;
            })->values();

            return response()->json([
                'ok' => true,
                'message' => 'Alert times retrieved successfully.',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching alerts: ' . $e->getMessage()], 500);
        }
    }

    // Toggle alert for a specific trip
    public function toggleAlerts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'trip_ids' => 'required|array',
                'trip_ids.*' => 'exists:trips,id',
            ]);

            if ($validator->fails()) {
                return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
            }

            $passenger = $request->user();
            $tripIds = $request->trip_ids;

            $results = [];

            foreach ($tripIds as $tripId) {
                $existingAlert = $passenger->tripAlerts()->where('trip_id', $tripId)->first();

                if ($existingAlert) {
                    // Remove alert
                    $existingAlert->delete();
                    $results[] = [
                        'trip_id' => $tripId,
                        'is_alert_active' => false,
                        'message' => 'Alert disabled successfully.'
                    ];
                } else {
                    // Add alert
                    $passenger->tripAlerts()->create([
                        'company_id' => $passenger->company_id,
                        'trip_id' => $tripId,
                    ]);
                    $results[] = [
                        'trip_id' => $tripId,
                        'is_alert_active' => true,
                        'message' => 'Alert enabled successfully.'
                    ];
                }
            }

            return response()->json([
                'ok' => true,
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error toggling alerts: ' . $e->getMessage()], 500);
        }
    }


    // Get user's active alerts
    public function myAlerts(Request $request)
    {
        try {
            $passenger = $request->user();

            $alerts = $passenger->tripAlerts()->with(['trip.route'])->get()->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'trip_id' => $alert->trip_id,
                    'route_name' => $alert->trip->route->name,
                    'departure_time' => date('g:i A', strtotime($alert->trip->departure_time)),
                    'direction' => $alert->trip->direction,
                    'notify_before_minutes' => $alert->notify_before_minutes,
                    'created_at' => $alert->created_at
                ];
            });

            return response()->json([
                'ok' => true,
                'message' => 'Active alerts retrieved successfully.',
                'data' => $alerts
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching alerts: ' . $e->getMessage()], 500);
        }
    }

    //update notify before timeing for alert
    public function updateAlertTiming(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'alert_timing' => 'required|integer|min:1|max:60'
        ]);
        if ($validator->fails()) {
            return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
        }
        $user->alert_timing = $request->alert_timing;
        $user->save();
        return response_success('Alert timing updated successfully.', ['alert_timing' => $user->alert_timing]);
    }
}
