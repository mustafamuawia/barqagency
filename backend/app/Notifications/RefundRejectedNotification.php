<?php

namespace App\Notifications;

use App\Models\Refund;
use Illuminate\Notifications\Notification;

class RefundRejectedNotification extends Notification
{
    public function __construct(private readonly Refund $refund) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(?object $notifiable): array
    {
        $message = "تم رفض طلب الاسترداد رقم {$this->refund->refund_number} للطلب {$this->refund->order?->order_number}.";
        if ($this->refund->admin_remarks) {
            $message .= " ملاحظة الإدارة: {$this->refund->admin_remarks}";
        }

        return [
            'type'         => 'refund_rejected',
            'title'        => 'تم رفض طلب الاسترداد',
            'message'      => $message,
            'link'         => '/agency/refunds',
            'reference_id' => $this->refund->id,
        ];
    }
}
