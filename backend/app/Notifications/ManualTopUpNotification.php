<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManualTopUpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected float $amount;
    protected User $staff;

    public function __construct(float $amount, User $staff)
    {
        $this->amount = $amount;
        $this->staff = $staff;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Cash Top-up Successful')
                    ->markdown('emails.payment.manual_topup', [
                        'user' => $notifiable,
                        'amount' => number_format($this->amount, 2),
                        'staff_name' => $this->staff->name,
                        'topup_date' => now()->toFormattedDateString(),
                    ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Cash Top-up Successful',
            'message' => "A cash top-up of $" . number_format($this->amount, 2) . " has been processed by our staff.",
            'amount' => $this->amount,
        ];
    }
}
