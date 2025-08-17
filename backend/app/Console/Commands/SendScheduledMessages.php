<?php

namespace App\Console\Commands;

use App\Models\Message;
use App\Services\Admin\MessageService;
use Illuminate\Console\Command;

class SendScheduledMessages extends Command
{
    protected $signature = 'messages:send-scheduled';
    protected $description = 'Send all messages that are scheduled to be sent';

    public function handle(MessageService $messageService)
    {
        $this->info('Checking for scheduled messages to send...');

        $messagesToSend = Message::where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($messagesToSend->isEmpty()) {
            $this->info('No scheduled messages found.');
            return 0;
        }

        $this->info($messagesToSend->count() . ' message(s) found. Sending...');

        foreach ($messagesToSend as $message) {
            $messageService->sendMessage($message, $message->recipient_type);
        }

        $this->info('Scheduled messages sent successfully.');
        return 0;
    }
}
