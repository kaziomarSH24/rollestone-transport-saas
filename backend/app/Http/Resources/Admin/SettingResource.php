<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'company_name' => $this->company_name,
            'contact_email' => $this->contact_email,
            'subdomain' => $this->subdomain,
            'status' => $this->status,
            'stripe_publishable_key' => isset($this->stripe_publishable_key) ? substr($this->stripe_publishable_key, 0, 4) . '****' . substr($this->stripe_publishable_key, 7, 16) . '****' : null,
            'stripe_secret_key' => isset($this->stripe_secret_key) ? substr($this->stripe_secret_key, 0, 4) . '****' . substr($this->stripe_secret_key, 7, 16) . '****' : null,
            'stripe_webhook_secret' => isset($this->stripe_webhook_secret) ? substr($this->stripe_webhook_secret, 0, 4) . '****' . substr($this->stripe_webhook_secret, 7, 16) . '****' : null,
            'fare_rules' => $this->fare_rules,
            'zello_channel' => $this->zello_channel,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_id' => $this->user_id,
            'webhook_endpoint' => $this->webhook_endpoint,
        ];
    }
}
