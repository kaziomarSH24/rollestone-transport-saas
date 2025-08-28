<?php

namespace App\Http\Resources\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LiveDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'journey_id' => $this->id,
            'route_name' => $this->trip->route->name,
            'route_prefix' => $this->trip->route->route_prefix,
            'trip_number' => $this->trip->trip_number,
            'direction' => $this->trip->direction,
            'departure_time' => Carbon::parse($this->trip->departure_time)->format('h:i A'),
            'driver_name' => $this->driver->user->name,
            'passengers' => $this->passenger_count ?? 0,
            'progress' => $this->progress,
            'status' => $this->status,
        ];
    }
}
