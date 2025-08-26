<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Notifications\Channels\FirebaseChannel;


class TripAlertNotification extends Notification
{
    protected string $routeName;
    protected string $departureTime;
    protected int $minutesBefore;
    protected int $tripId;
    protected array $tokens;

    public function __construct(string $routeName, string $departureTime, int $minutesBefore, int $tripId, array $tokens)
    {
        $this->routeName = $routeName;
        $this->departureTime = $departureTime;
        $this->minutesBefore = $minutesBefore;
        $this->tripId = $tripId;
        $this->tokens = $tokens;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', FirebaseChannel::class];
    }


    public function toArray(object $notifiable): array
    {
        $departureTime = \Carbon\Carbon::parse($this->departureTime)->format('h:i A');
        return [
            'title' => 'Trip Reminder: ' . $this->routeName,
            'body' => "Your trip is scheduled to depart at {$departureTime}. Get ready!",
            'trip_id' => $this->tripId,
        ];
    }

    //for Firebase
    public function toFirebase(object $notifiable): array
    {
        $departureTime = \Carbon\Carbon::parse($this->departureTime)->format('h:i A');

        return [
            'tokens' => $this->tokens,
            'title' => 'Trip Reminder: ' . $this->routeName,
            'body' => "Your trip is scheduled to depart at {$departureTime}. Get ready!",
            'data' => [
                'trip_id' => (string)$this->tripId,
                'type' => 'trip_alert',
                'route_name' => $this->routeName,
                'departure_time' => $this->departureTime,
                'minutes_before' => (string)$this->minutesBefore,
            ],
        ];
    }
}
