<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class TicketUploadedNotification extends Notification
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
            'type' => 'ticket_uploaded',
            'title' => 'تذكرة السفر جاهزة',
            'message' => "تم إصدار تذكرة السفر للطلب رقم {$this->order->order_number} وهي جاهزة للتنزيل الآن.",
            'link' => "/agency/orders/{$this->order->id}",
            'reference_id' => $this->order->id,
        ];
    }
}
