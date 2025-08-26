<?php

namespace App\Console\Commands;

use App\Jobs\SendTripAlert;
use App\Models\TripAlert;
use App\Models\Trip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ScheduleTripAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:schedule-trip-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Schedule trip alerts to be sent at appropriate times';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting trip alert scheduling...');

        // Get all active trip alerts with their trips and users
        $tripAlerts = TripAlert::with(['trip.route', 'user'])
            ->whereHas('trip', function($query) {
                $query->where('is_active', 1);
            })
            ->whereHas('user.deviceTokens') // Ensure user has device tokens
            ->get();

        $scheduledCount = 0;
        $now = Carbon::now();

        foreach ($tripAlerts as $alert) {
            $trip = $alert->trip;
            $departureTime = Carbon::today()->setTimeFromTimeString($trip->departure_time);

            // Calculate notification time (departure time minus notify_before_minutes)
            $notificationTime = $departureTime->subMinutes($alert->notify_before_minutes);

            // Only schedule if notification time is in the future and within next 24 hours
            if ($notificationTime->isAfter($now) && $notificationTime->isBefore($now->copy()->addDay())) {

                // Dispatch job to run at the calculated time
                SendTripAlert::dispatch($alert)->delay($notificationTime);

                $scheduledCount++;

                $this->line("Scheduled alert for {$alert->user->name} - {$trip->route->name} at {$notificationTime->format('H:i')}");
            }
        }

        $this->info("Successfully scheduled {$scheduledCount} trip alerts.");
        Log::info("Scheduled {$scheduledCount} trip alerts", ['timestamp' => $now]);

        return Command::SUCCESS;
    }
}
