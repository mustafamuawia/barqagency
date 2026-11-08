<?php

namespace App\Notifications;

use App\Models\Refund;
use Illuminate\Notifications\Notification;

class RefundApprovedNotification extends Notification
{
    public function __construct(private readonly Refund $refund) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(?object $notifiable): array
    {
        return [
            'type'         => 'refund_approved',
            'title'        => 'تمت الموافقة على طلب الاسترداد',
            'message'      => "تمت الموافقة على طلب الاسترداد رقم {$this->refund->refund_number} للطلب {$this->refund->order?->order_number}. سيتم إضافة المبلغ المسترد إلى محفظتك.",
            'link'         => '/agency/refunds',
            'reference_id' => $this->refund->id,
        ];
    }
}
