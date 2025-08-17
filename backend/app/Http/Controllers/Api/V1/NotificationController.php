<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{

    public function index()
    {
       try{
         $perPage = request()->get('per_page', 15); // Default to 15 if not specified
        $notifications = Auth::user()->notifications()->paginate($perPage);
        return $notifications;
       }catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Failed to fetch notifications: ' . $e->getMessage()], 500);
        }
        // return NotificationResource::collection($notifications);
    }


    public function stats()
    {
        return response()->json([
            'ok' => true,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }


    public function markAsRead(DatabaseNotification $notification)
    {

        try{
            if (Auth::id() !== $notification->notifiable_id) {
            return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true, 'message' => 'Notification marked as read.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Failed to mark notification as read: ' . $e->getMessage()], 500);
        }
    }


    public function markAllAsRead()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                 return response()->json([
                    'ok' => false,
                    'message' => 'User not authenticated.'
                ], 401); // 401 Unauthorized
            }
            $unreadNotifications = $user->unreadNotifications()->get();

            if ($unreadNotifications->isNotEmpty()) {

                $unreadNotifications->markAsRead();
            }

            return response()->json([
                'ok' => true,
                'message' => 'All unread notifications marked as read.'
            ]);
        } catch (Exception $e) {

            Log::error('Failed to mark all notifications as read: ' . $e->getMessage());

            // Avoid sending detailed exception messages to the client.
            return response()->json([
                'ok' => false,
                'message' => 'An unexpected error occurred.'
            ], 500);
        }
    }


    public function destroy(DatabaseNotification $notification)
    {
        try {
            if (Auth::id() !== $notification->notifiable_id) {
                return response()->json(['ok' => false, 'message' => 'Forbidden'], 403);
            }

            $notification->delete();

            return response()->json(['ok' => true, 'message' => 'Notification deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => 'Failed to delete notification: ' . $e->getMessage()], 500);
        }
    }
}
