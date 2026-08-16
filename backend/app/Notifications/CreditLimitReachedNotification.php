<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class CreditLimitReachedNotification extends Notification
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
            'type' => 'credit_limit',
            'title' => 'تم الوصول إلى حد الائتمان المسموح',
            'message' => "تعذّرت الموافقة على الطلب رقم {$this->order->order_number} لأن ذلك يتجاوز حد الائتمان المسموح لمحفظتكم. يرجى سداد المستحقات أو التواصل مع الإدارة.",
            'link' => "/agency/orders/{$this->order->id}",
            'reference_id' => $this->order->id,
        ];
    }
}
