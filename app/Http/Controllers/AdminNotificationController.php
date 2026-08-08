<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\AdminNotification;

class AdminNotificationController extends Controller
{
    public function index()
    {
        $notifications = AdminNotification::orderBy('created_at', 'desc')->take(50)->get();
        return response()->json($notifications);
    }

    public function markAsRead(AdminNotification $notification)
    {
        $notification->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $type = $request->query('type');
        $query = AdminNotification::where('is_read', false);
        if ($type) {
            $query->where('type', $type);
        }
        $query->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }
}
