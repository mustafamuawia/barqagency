<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class QuoteUpdatedNotification extends Notification
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
            'type' => 'quote_updated',
            'title' => 'تم تعديل عرض السعر',
            'message' => "قامت الإدارة بتعديل عرض السعر للطلب رقم {$this->order->order_number}. يرجى مراجعة السعر ومواعيد المغادرة قبل الموافقة.",
            'link' => "/agency/orders/{$this->order->id}",
            'reference_id' => $this->order->id,
        ];
    }
}
