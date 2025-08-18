<?php

namespace App\Http\Resources\Driver;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->relationLoaded('trip') && $this->trip->relationLoaded('route') && $this->trip->route->relationLoaded('stops')) {
            if ($this->trip->direction === 'outbound') {
            $location = [
                'latitude' => $this->trip->route->stops->first()->latitude,
                'longitude' => $this->trip->route->stops->first()->longitude,
                'location_name' => $this->trip->route->stops->first()->location_name,
            ];
            } else {
            $location = [
                'latitude' => $this->trip->route->stops->last()->latitude,
                'longitude' => $this->trip->route->stops->last()->longitude,
                'location_name' => $this->trip->route->stops->last()->location_name,
            ];
            }
        } else {
            $location = null;
        }
        return [
            'id' => $this->id,
            'trip_id' => $this->trip_id,
            'driver_id' => $this->driver_id,
            'fleet_number' => $this->fleet_number,
            'journey_date' => $this->journey_date,
            'actual_departure_time' => $this->actual_departure_time,
            'actual_arrival_time' => $this->actual_arrival_time,
            'current_lat' => $this->current_lat,
            'current_lng' => $this->current_lng,
            'status' => strtolower($this->status), // 'Blocked' to 'blocked'
            'location'=> $location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'trip' => new TripResource($this->whenLoaded('trip')),
        ];
    }
}
