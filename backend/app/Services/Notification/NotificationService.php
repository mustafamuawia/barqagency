<?php

namespace App\Services\Notification;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationService
{
    public function list(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->notifications()->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markRead(User $user, string $id): DatabaseNotification
    {
        $notification = $user->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return $notification;
    }

    public function markAllRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
