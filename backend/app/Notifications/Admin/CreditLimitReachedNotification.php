<?php

namespace App\Notifications\Admin;

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
            'title' => 'وكالة وصلت إلى حد الائتمان المسموح',
            'message' => "حاولت وكالة {$this->order->agency?->name} الموافقة على الطلب رقم {$this->order->order_number} لكن ذلك يتجاوز حد الائتمان المسموح لمحفظتها.",
            'link' => '/admin/agencies',
            'reference_id' => $this->order->id,
        ];
    }
}
