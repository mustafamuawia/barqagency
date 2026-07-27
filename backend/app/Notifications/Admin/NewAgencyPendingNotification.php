<?php

namespace App\Notifications\Admin;

use App\Models\TravelAgency;
use Illuminate\Notifications\Notification;

class NewAgencyPendingNotification extends Notification
{
    public function __construct(private readonly TravelAgency $agency)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(?object $notifiable): array
    {
        return [
            'type' => 'agency',
            'title' => 'طلب تسجيل وكالة جديد',
            'message' => "وكالة جديدة \"{$this->agency->name}\" سجلت وبانتظار الموافقة",
            'link' => '/admin/agencies',
            'reference_id' => $this->agency->id,
        ];
    }
}
