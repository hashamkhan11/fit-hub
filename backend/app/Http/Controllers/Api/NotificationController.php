<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->notifications()->orderByDesc('created_at')->limit(100)->get();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->whereNull('read_at')->count(),
        ]);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifications marked as read.']);
    }

    public function destroy(Request $request, int $id)
    {
        $request->user()->notifications()->where('id', $id)->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json(['message' => 'All notifications deleted.']);
    }
}
