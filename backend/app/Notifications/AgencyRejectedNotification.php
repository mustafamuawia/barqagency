<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyRejectedNotification extends Notification
{
    public function __construct(private readonly ?string $reason)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('طلب تسجيل الوكالة - Barq Agency')
            ->line('نأسف لإبلاغك بأنه تم رفض طلب تسجيل وكالتك في منصة Barq Agency.');

        if ($this->reason) {
            $mail->line('سبب الرفض: '.$this->reason);
        }

        return $mail->line('لأي استفسار، يرجى التواصل مع فريق الدعم.');
    }
}
