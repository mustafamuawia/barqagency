<?php

namespace App\Notifications\Admin;

use App\Models\Refund;
use Illuminate\Notifications\Notification;

class NewRefundRequestNotification extends Notification
{
    public function __construct(private readonly Refund $refund)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(?object $notifiable): array
    {
        return [
            'type' => 'refund',
            'title' => 'طلب استرداد جديد',
            'message' => "طلب استرداد رقم {$this->refund->refund_number} من وكالة {$this->refund->agency?->name} بانتظار المراجعة",
            'link' => '/admin/refunds',
            'reference_id' => $this->refund->id,
        ];
    }
}
