<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MessageRequest;
use App\Models\Message;
use App\Services\Admin\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    protected MessageService $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
        // Middleware for authorization
        $this->middleware('can:view messages')->only(['index', 'show', 'dashboardStats']);
        $this->middleware('can:send messages')->only(['store']);
        $this->middleware('can:edit messages')->only(['update']);
        $this->middleware('can:delete messages')->only(['destroy']);
    }


    // dashboard stats
    public function dashboardStats()
    {
        try {
            $totalMessages = $this->messageService->getTotalMessagesCount();
            $draftMessages = $this->messageService->getDraftMessagesCount();
            $scheduledMessages = $this->messageService->getScheduledMessagesCount();
            $sentMessages = $this->messageService->getSentMessagesCount();

            $data = [
                'total_messages' => $totalMessages,
                'draft_messages' => $draftMessages,
                'scheduled_messages' => $scheduledMessages,
                'sent_messages' => $sentMessages,
            ];
            return response_success('Message stats retrieved successfully.', $data);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Failed to fetch message stats: ' . $e->getMessage()], 500);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status', null); // Optional status filter

            $quearyCallback = function ($query) use ($status) {
                if ($status) {
                    $query->where('status', $status);
                }
            };
            $messages = $this->messageService->getAll([], $perPage, $quearyCallback);
            if ($messages->isEmpty()) {
                return response_error('No messages found.', [], 404);
            }
            return response_success('Messages retrieved successfully.', $messages);
        } catch (\Exception $e) {
            return response_error('Failed to fetch messages: ' . $e->getMessage(), [], 500);
        }
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(MessageRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $status = 'draft';
            if ($validatedData['action'] === 'schedule') {
                $status = 'scheduled';
            }
            $validatedData['status'] = $status;
            $validatedData['sender_id'] = auth()->id();

            $message = $this->messageService->create($validatedData);
            if ($message) {
                // If the action is to send immediately
                if ($validatedData['action'] === 'send_now') {
                    $this->messageService->sendMessage($message, $validatedData['recipient_type']);
                }

                return response_success('Message created successfully.', $message);
            }
            return response_error('Failed to create message.', [], 500);
        } catch (\Exception $e) {
            return response_error('Failed to create message: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $message = $this->messageService->getById((int)$id);
            if (!$message) {
                return response_error('Message not found.', [], 404);
            }
            return response_success('Message retrieved successfully.', $message);
        } catch (\Exception $e) {
            return response_error('Failed to retrieve message: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MessageRequest $request, string $id)
    {
        try {
            $validatedData = $request->validated();
            $message = $this->messageService->getById($id);
            // dd($validatedData)  ;
            if (!$message) {
                return response_error('Message not found.', [], 404);
            }

            if($message->status !== 'draft' && $message->status !== 'scheduled') {
                return response_error('Only draft or scheduled messages can be updated.', [], 403);
            }

            $status = 'draft';
            if ($validatedData['action'] === 'schedule') {
                $status = 'scheduled';
            }
            $validatedData['status'] = $status;
            $validatedData['sender_id'] = auth()->id();

            // Update the message
            $updatedMessage = $this->messageService->update($message->id, $validatedData);
            if ($updatedMessage) {
                return response_success('Message updated successfully.', $updatedMessage);
            }
            return response_error('Failed to update message.', [], 500);
        } catch (\Exception $e) {
            return response_error('Failed to update message: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $message = $this->messageService->getById($id);
            if (!$message) {
                return response_error('Message not found.', [], 404);
            }

            // Only allow deletion of draft or scheduled messages
            if ($message->status !== 'draft' && $message->status !== 'scheduled') {
                return response_error('Only draft or scheduled messages can be deleted.', [], 403);
            }

            $this->messageService->delete($id);
            return response_success('Message deleted successfully.', [], 200);
        } catch (\Exception $e) {
            return response_error('Failed to delete message: ' . $e->getMessage(), [], 500);
        }
    }
}
