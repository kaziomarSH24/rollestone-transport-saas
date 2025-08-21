<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $baseData = [
            'id' => $this->id,
            'is_read' => $this->read_at !== null,
            'created_at_human' => $this->created_at->diffForHumans(),
        ];

        switch ($this->type) {
            case 'App\Notifications\Admin\CustomAlert':
                return array_merge($baseData, [
                    'type' => 'admin_alert',
                    'title' => $this->data['subject'],
                    'message' => $this->data['body'],
                ]);

            case 'App\Notifications\PaymentSuccessNotification':
                return array_merge($baseData, [
                    'type' => 'payment_success',
                    'title' => $this->data['title'],
                    'message' => $this->data['message'],
                    'extra_data' => [
                        'amount' => $this->data['amount'],
                        'charge_id' => $this->data['charge_id'],
                    ]
                ]);

            case 'App\Notifications\ManualTopUpNotification':
                return array_merge($baseData, [
                    'type' => 'manual_topup',
                    'title' => $this->data['title'],
                    'message' => $this->data['message'],
                    'extra_data' => [
                        'amount' => $this->data['amount'],
                    ]
                ]);

            case 'App\Notifications\ManualRefundNotification':
                return array_merge($baseData, [
                    'type' => 'manual_refund',
                    'title' => $this->data['title'],
                    'message' => $this->data['message'],
                    'extra_data' => [
                        'amount' => $this->data['amount'],
                    ]
                ]);

            default:
                return array_merge($baseData, [
                    'type' => 'generic',
                    'title' => 'New Notification',
                    'message' => 'You have a new notification.',
                ]);
        }
    }
}
