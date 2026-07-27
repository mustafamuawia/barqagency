<?php

namespace App\Services\Notification;

use App\Events\AdminNotificationBroadcast;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class AdminNotifier
{
    /** Persist the notification for every admin/super_admin and push it to them in real time. */
    public function send(Notification $notification): void
    {
        $admins = User::admins()->get();

        if ($admins->isEmpty()) {
            return;
        }

        NotificationFacade::send($admins, $notification);

        event(new AdminNotificationBroadcast(
            $admins->pluck('id')->all(),
            array_merge($notification->toArray(null), ['created_at' => now()->toIso8601String()]),
        ));
    }
}
