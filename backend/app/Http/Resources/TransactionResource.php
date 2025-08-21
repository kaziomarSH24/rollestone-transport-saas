<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $routeName = $this->journey?->trip?->route?->name ?? 'Wallet Transaction';


        return [
            'id' => $this->id,
            'date_time' => Carbon::parse($this->created_at)->format('F j, Y - h:i A'),
            'description' => $routeName,
            'type' => $this->type, // 'TopUp', 'TripFare', 'Refund'
            'payment_method' => $this->payment_method, // 'Wallet', 'Cash', 'Stripe'
            'amount' => abs($this->amount),
            'status' => $this->status,
             $this->mergeWhen($this->type === 'TripFare' && $this->fare_details, [
                'fare_details' => json_decode($this->fare_details)
            ]),
        ];
    }
}
