<?php

namespace App\Notifications\Admin;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomAlert extends Notification implements ShouldQueue
{
     use Queueable;

    public Message $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
     public function toDatabase(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'subject' => $this->message->subject,
            'body' => $this->message->body,
        ];
    }
}
