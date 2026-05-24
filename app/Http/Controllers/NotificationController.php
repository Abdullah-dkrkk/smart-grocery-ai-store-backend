<?php

namespace App\Http\Controllers;

use App\Models\NotificationModel;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = NotificationModel::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return $this->paginateResponse($notifications, 'Notifications retrieved');
    }

    public function markAsRead(Request $request, $id)
    {
        $notification = NotificationModel::where('user_id', $request->user()->id)
            ->find($id);

        if (!$notification) {
            return $this->errorResponse('Notification not found', 404);
        }

        $notification->update(['is_read' => true]);

        return $this->successResponse($notification, 'Notification marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        NotificationModel::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return $this->successResponse(null, 'All notifications marked as read');
    }
}
