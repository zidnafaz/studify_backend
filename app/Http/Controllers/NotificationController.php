<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user notifications (last 7 days)
     */
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $notifications
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->find($id);

        if (!$notification) {
            return response()->json(['message' => __('messages.notification_not_found')], 404);
        }

        $notification->update(['is_read' => true]);

        return response()->json(['message' => __('messages.notification_marked_read')]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['message' => __('messages.all_notifications_marked_read')]);
    }
}
