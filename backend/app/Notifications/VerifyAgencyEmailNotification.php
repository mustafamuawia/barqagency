<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyAgencyEmailNotification extends Notification
{
    public function __construct(private readonly string $token)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/verify-email?token='.$this->token.'&email='.urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('تفعيل بريدك الإلكتروني - برق')
            ->line('شكراً لتسجيلك في منصة برق. يرجى تفعيل بريدك الإلكتروني لإكمال إنشاء الحساب.')
            ->action('تفعيل البريد الإلكتروني', $url)
            ->line('بعد التفعيل، سيتم مراجعة طلبك من قبل الإدارة قبل منحك صلاحية الوصول الكامل.');
    }
}
