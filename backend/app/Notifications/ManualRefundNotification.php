<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ManualRefundNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected float $amount;
    protected User $staff;

    /**
     * Create a new notification instance.
     */
    public function __construct(float $amount, User $staff)
    {
        $this->amount = $amount;
        $this->staff = $staff;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Cash Refund Processed')
                    ->markdown('emails.payment.manual_refund', [
                        'user' => $notifiable,
                        'amount' => number_format($this->amount, 2),
                        'staff_name' => $this->staff->name,
                        'refund_date' => now()->toFormattedDateString(),
                    ]);
    }

    /**
     * Get the array representation of the notification for the database.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Cash Refund Processed',
            'message' => "A cash refund of $" . number_format($this->amount, 2) . " has been processed by our staff.",
            'amount' => $this->amount,
        ];
    }
}
