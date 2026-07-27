<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;

class AppNotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function index(Request $request)
    {
        return response()->json($this->notificationService->list($request->user()));
    }

    public function unreadCount(Request $request)
    {
        return response()->json(['unread_count' => $this->notificationService->unreadCount($request->user())]);
    }

    public function markRead(Request $request, string $id)
    {
        $notification = $this->notificationService->markRead($request->user(), $id);

        return response()->json(['notification' => $notification]);
    }

    public function markAllRead(Request $request)
    {
        $this->notificationService->markAllRead($request->user());

        return response()->json(['message' => 'تم تحديد جميع الإشعارات كمقروءة']);
    }
}
