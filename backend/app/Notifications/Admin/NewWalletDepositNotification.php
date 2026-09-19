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
        $amount = number_format((float) $this->transaction->money_equivalent, 2) . ' ج.س';
        $referenceNumber = $this->transaction->metadata['reference_number'] ?? null;
        $suffix = $referenceNumber ? " (مرجع: {$referenceNumber})" : '';

        return [
            'type' => 'deposit',
            'title' => 'طلب إيداع جديد',
            'message' => "طلب إيداع بقيمة {$amount} من وكالة {$agencyName}{$suffix} بانتظار المراجعة",
            'link' => '/admin/wallet/topup',
            'reference_id' => $this->transaction->id,
            'reference_number' => $referenceNumber,
            'receipt_url' => $this->transaction->receipt_url,
        ];
    }
}
