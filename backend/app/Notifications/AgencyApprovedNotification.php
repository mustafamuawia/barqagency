<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AgencyApprovedNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim(config('app.frontend_url'), '/').'/login';

        return (new MailMessage)
            ->subject('تمت الموافقة على حساب وكالتك - Barq Agency')
            ->line('يسعدنا إبلاغك بأنه تمت الموافقة على طلب تسجيل وكالتك في منصة Barq Agency.')
            ->action('تسجيل الدخول الآن', $url)
            ->line('يمكنك الآن الوصول الكامل للوحة تحكم الوكيل.');
    }
}
