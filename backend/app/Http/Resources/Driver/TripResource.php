<?php

namespace App\Http\Resources\Driver;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'route_id' => $this->route_id,
            'departure_time' => Carbon::parse($this->departure_time)->format('h:i A'),
            'direction' => $this->direction,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'starting_point' => $this->relationLoaded('route') && $this->route->relationLoaded('stops')
                ? ($this->direction === 'outbound'
                    ? optional($this->route->stops->first())->location_name
                    : optional($this->route->stops->last())->location_name)
                : null,
            'route' => new RouteResource($this->whenLoaded('route')),
        ];
    }
}
