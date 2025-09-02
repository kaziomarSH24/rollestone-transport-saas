<?php

namespace App\Notifications\Admin;

use App\Models\Message;
use App\Notifications\Channels\FirebaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
       // Ensure deviceTokens relationship is loaded
        $notifiable->loadMissing('deviceTokens');

        $channels = ['database'];

        if ($notifiable->deviceTokens && $notifiable->deviceTokens->isNotEmpty()) {
            $channels[] = FirebaseChannel::class;
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification for the database.
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

    /**
     * Get the array representation of the notification for Firebase.
     *
     * @return array<string, mixed>
     */
    public function toFirebase(object $notifiable): array
    {
        
        $tokens = $notifiable->deviceTokens->pluck('token')->toArray();

        return [
            'tokens' => $tokens,
            'title' => $this->message->subject,
            'body' => $this->message->body,
            'data' => [
                'message_id' => (string)$this->message->id,
                'type' => 'admin_custom_alert',
            ],
        ];
    }
}
