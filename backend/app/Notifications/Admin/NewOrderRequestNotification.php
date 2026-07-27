<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class NewOrderRequestNotification extends Notification
{
    public function __construct(private readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(?object $notifiable): array
    {
        return [
            'type' => 'order',
            'title' => 'طلب حجز جديد',
            'message' => "طلب حجز جديد رقم {$this->order->order_number} من وكالة {$this->order->agency?->name} بانتظار التسعير",
            'link' => '/admin/orders',
            'reference_id' => $this->order->id,
        ];
    }
}
