<?php

namespace App\Http\Controllers\api\V1\Passenger;

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
            $routes = Route::with(['trips' => function($query) {
                $query->where('is_active', 1)->orderBy('departure_time');
            }])->get();

            $data = $routes->map(function($route) use ($userAlerts) {
                // Group trips by direction for this route
                $inboundTrips = $route->trips->where('direction', 'inbound');
                $outboundTrips = $route->trips->where('direction', 'outbound');

                // Extract and format times with alert status
                $inboundTimes = $inboundTrips->map(function($trip) use ($userAlerts) {
                    return [
                        'trip_id' => $trip->id,
                        'time' => date('g:i A', strtotime($trip->departure_time)),
                        'is_alert_active' => in_array($trip->id, $userAlerts)
                    ];
                })->values();

                $outboundTimes = $outboundTrips->map(function($trip) use ($userAlerts) {
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
            })->filter(function($route) {
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
    public function toggleAlert(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'trip_id' => 'required|exists:trips,id',
                'notify_before_minutes' => 'nullable|integer|min:1|max:60'
            ]);

            if ($validator->fails()) {
                return response_error($validator->errors()->first(), $validator->errors()->toArray(), 422);
            }


            $passenger = $request->user();
            $tripId = $request->trip_id;
            $notifyBefore = $request->notify_before_minutes ?? 5;

            // Check if alert already exists
            $existingAlert = $passenger->tripAlerts()->where('trip_id', $tripId)->first();

            if ($existingAlert) {
                // Remove alert
                $existingAlert->delete();
                $message = 'Alert disabled successfully.';
                $isActive = false;
            } else {
                // Add alert
                $passenger->tripAlerts()->create([
                    'company_id' => $passenger->company_id,
                    'trip_id' => $tripId,
                    'notify_before_minutes' => $notifyBefore
                ]);
                $message = 'Alert enabled successfully.';
                $isActive = true;
            }

            return response()->json([
                'ok' => true,
                'message' => $message,
                'data' => [
                    'trip_id' => $tripId,
                    'is_alert_active' => $isActive,
                    'notify_before_minutes' => $notifyBefore
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error toggling alert: ' . $e->getMessage()], 500);
        }
    }

    // Update FCM token for push notifications
    public function updateFcmToken(Request $request)
    {
        try {
            $request->validate([
                'fcm_token' => 'required|string'
            ]);

            $passenger = $request->user();
            $passenger->update(['fcm_token' => $request->fcm_token]);

            return response()->json([
                'ok' => true,
                'message' => 'FCM token updated successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating FCM token: ' . $e->getMessage()], 500);
        }
    }

    // Get user's active alerts
    public function myAlerts(Request $request)
    {
        try {
            $passenger = $request->user();

            $alerts = $passenger->tripAlerts()->with(['trip.route'])->get()->map(function($alert) {
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
}
