<?php

namespace App\Services\Admin;

use App\Services\BaseService;
use App\Models\Message;
use App\Models\User;
use App\Notifications\Admin\CustomAlert;
use Illuminate\Support\Facades\Notification;

class MessageService extends BaseService
{
    /**
     * The model class name.
     *
     * @var string
     */
    protected string $modelClass = Message::class;

    public function __construct()
    {
        // Ensure BaseService initializes the model instance
        parent::__construct();
    }

    /**
     * Sends a message to the specified recipients.
     */
    public function sendMessage(Message $message, string $recipientType): void
    {
        $recipients = collect();

        switch ($recipientType) {
            case 'all':
                $recipients = User::all();
                break;
            case 'drivers':
                $recipients = User::role('Driver')->get();
                break;
            case 'passengers':
                $recipients = User::role('Passenger')->get();
                break;
            default:
                throw new \InvalidArgumentException("Invalid recipient type: {$recipientType}");
        }

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new CustomAlert($message));
            $message->update(['status' => 'sent']);
        }
    }

    // Get total messages count
    public function getTotalMessagesCount(): int
    { 
        return $this->modelClass::count();
    }
    // Get draft messages count
    public function getDraftMessagesCount(): int
    {
        return $this->modelClass::where('status', 'draft')->count();
    }
    // Get scheduled messages count
    public function getScheduledMessagesCount(): int
    {
        return $this->modelClass::where('status', 'scheduled')->count();
    }
    // Get sent messages count
    public function getSentMessagesCount(): int
    {
        return $this->modelClass::where('status', 'sent')->count();
    }

}
