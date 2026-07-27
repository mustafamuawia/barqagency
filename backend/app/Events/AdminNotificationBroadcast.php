<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class AdminNotificationBroadcast implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * @param  int[]  $adminIds
     */
    public function __construct(public array $adminIds, public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        return array_map(fn ($id) => new PrivateChannel('App.Models.User.'.$id), $this->adminIds);
    }

    public function broadcastAs(): string
    {
        return 'admin.notification';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
