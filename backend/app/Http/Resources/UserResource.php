<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'phone_number' => $this->phone_number,
            'avatar' => $this->avatar,
            'address' => $this->address,
            'rider_type' => $this->when(!is_null($this->rider_type), $this->rider_type),
            'qr_code_number' => $this->when(!is_null($this->qr_code_number), $this->qr_code_number),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            $this->mergeWhen($this->relationLoaded('wallet'), [
                'balance' => $this->wallet ? number_format($this->wallet->balance, 2) : '0.00',
                'is_auto_top_up_enabled' => $this->wallet ? (bool)$this->wallet->auto_topup_enabled : false,
                'auto_top_up_amount' => $this->wallet ? number_format($this->wallet->auto_topup_amount, 2) : '0.00',
                'auto_top_up_threshold' => $this->wallet ? number_format($this->wallet->auto_topup_threshold, 2) : '0.00',
            ]),

            $this->mergeWhen($this->relationLoaded('paymentMethods'), [
                'card_number' => $this->paymentMethods->where('is_default', true)->first()->last_four ?? null,
            ]),

            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            $this->mergeWhen($this->relationLoaded('roles'), [
                'permissions' => $this->getAllPermissions()->pluck('name'),
            ]),
            $this->mergeWhen($this->relationLoaded('transactions'), [
                'total_trips' => $this->transactions->where('type', 'TripFare')->count(),
                'total_spent' => abs(number_format($this->transactions->where('type', 'TripFare')->sum('amount'), 2)),
            ]),
        ];
    }
}
