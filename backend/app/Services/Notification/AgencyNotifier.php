<?php

namespace App\Services\Notification;

use App\Events\AgencyNotificationBroadcast;
use App\Models\TravelAgency;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class AgencyNotifier
{
    /** Persist the notification for every user linked to the agency and push it to them in real time. */
    public function send(TravelAgency $agency, Notification $notification): void
    {
        $users = $agency->users()->with('user')->get()->pluck('user')->filter()->values();

        if ($users->isEmpty()) {
            return;
        }

        NotificationFacade::send($users, $notification);

        event(new AgencyNotificationBroadcast(
            $users->pluck('id')->all(),
            array_merge($notification->toArray(null), ['created_at' => now()->toIso8601String()]),
        ));
    }
}
