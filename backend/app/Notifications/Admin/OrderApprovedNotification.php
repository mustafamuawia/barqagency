<?php

namespace App\Notifications\Admin;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderApprovedNotification extends Notification
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
            'title' => 'تمت الموافقة على الطلب',
            'message' => "وافقت وكالة {$this->order->agency?->name} على عرض السعر للطلب رقم {$this->order->order_number}",
            'link' => '/admin/orders',
            'reference_id' => $this->order->id,
        ];
    }
}
