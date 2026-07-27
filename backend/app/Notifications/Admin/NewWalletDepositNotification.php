<?php

namespace App\Notifications\Admin;

use App\Models\WalletTransaction;
use Illuminate\Notifications\Notification;

class NewWalletDepositNotification extends Notification
{
    public function __construct(private readonly WalletTransaction $transaction)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(?object $notifiable): array
    {
        $agencyName = $this->transaction->wallet?->agency?->name;
        $amount = number_format((float) $this->transaction->amount, 2);

        return [
            'type' => 'deposit',
            'title' => 'طلب إيداع جديد',
            'message' => "طلب إيداع بقيمة {$amount} من وكالة {$agencyName} بانتظار المراجعة",
            'link' => '/admin/wallet/topup',
            'reference_id' => $this->transaction->id,
        ];
    }
}
