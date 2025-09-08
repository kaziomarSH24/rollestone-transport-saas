<?php

namespace App\Http\Resources\Passenger;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RouteSummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstTrip = $this->first_trip_time ? Carbon::parse($this->first_trip_time)->format('g:i A') : 'N/A';
        $lastTrip = $this->last_trip_time ? Carbon::parse($this->last_trip_time)->format('g:i A') : 'N/A';

        return [
            'id' => $this->id,
            'name' => $this->name,
            'route_prefix' => $this->route_prefix,
            'outbound_trips_count' => $this->outbound_trip_count,
            'inbound_trips_count' => $this->inbound_trip_count,
            'time_range' => "{$firstTrip} - {$lastTrip}",
            'first_trip' => "First trip: {$firstTrip}",
            'last_trip' => "Last trip: {$lastTrip}",
            'status' => $this->status,
        ];
    }
}
