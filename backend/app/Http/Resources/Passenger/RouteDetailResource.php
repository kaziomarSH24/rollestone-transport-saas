<?php
namespace App\Http\Resources\Passenger;

use App\Http\Resources\Driver\JourneyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\Passenger\RouteScheduleService;

class RouteDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $scheduleService = new RouteScheduleService();
        $schedules = $scheduleService->generateStopCentricSchedule($this->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'full_name' => $this->full_name,
            'google_map_link' => $this->google_map_link,
            'route_prefix' => $this->route_prefix,
            'start_latitude' => $this->stops->first()?->latitude,
            'start_longitude' => $this->stops->first()?->longitude,
            'end_latitude' => $this->stops->last()?->latitude,
            'end_longitude' => $this->stops->last()?->longitude,
            'status' => $this->status,
            'fares' => $this->fares->where('payment_method', 'Wallet')->map(function ($fare) {
                return [
                    'type' => $fare->passenger_type,
                    'price' => (float) $fare->amount,
                ];
            })->values(),


            'outbound_schedule' => $schedules['outbound_schedule'],
            'inbound_schedule' => $schedules['inbound_schedule'],
            //journeys with ongoing status
            'ongoing_journeys' => JourneyResource::collection($this->whenLoaded('journeys')),
        ];
    }
}
