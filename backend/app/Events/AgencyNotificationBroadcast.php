<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class AgencyNotificationBroadcast implements ShouldBroadcastNow
{
    use SerializesModels;

    /**
     * @param  int[]  $userIds
     */
    public function __construct(public array $userIds, public array $payload)
    {
    }

    public function broadcastOn(): array
    {
        return array_map(fn ($id) => new PrivateChannel('App.Models.User.'.$id), $this->userIds);
    }

    public function broadcastAs(): string
    {
        return 'agency.notification';
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
