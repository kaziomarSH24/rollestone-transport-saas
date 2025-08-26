<?php

namespace App\Jobs;

use App\Models\TripAlert;
use App\Notifications\TripAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTripAlert implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public TripAlert $tripAlert;

    /**
     * Create a new job instance.
     */
    public function __construct(TripAlert $tripAlert)
    {
        $this->tripAlert = $tripAlert;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $latestAlert = TripAlert::find($this->tripAlert->id);
            if ($latestAlert && $latestAlert->trip->is_active) {

                $user = $latestAlert->user;
                $trip = $latestAlert->trip;
                $route = $trip->route;

                $tokens = $user->deviceTokens->pluck('token')->toArray();
                // Check if user has FCM oken
                if (empty($tokens)) {
                    Log::warning('User has no device tokens to send notification.', ['user_id' => $user->id]);
                    return;
                }

                // Format departure time
                $departureTime = date('g:i A', strtotime($trip->departure_time));

                // Send notification using Laravel notification
                $user->notify(new TripAlertNotification(
                    $route->name,
                    $departureTime,
                    $latestAlert->notify_before_minutes,
                    $trip->id,
                    $tokens
                ));

                Log::info('Trip alert sent successfully', [
                    'user_id' => $user->id,
                    'trip_id' => $trip->id,
                    'route_name' => $route->name
                ]);
            } else {
                Log::warning('Skipping notification for a cancelled or deleted alert.', [
                    'trip_alert_id' => $this->tripAlert->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SendTripAlert job failed', [
                'error' => $e->getMessage(),
                'trip_alert_id' => $this->tripAlert->id
            ]);

            // You can choose to retry or fail the job
            throw $e;
        }
    }
}
