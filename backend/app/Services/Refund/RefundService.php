<?php

namespace App\Services\Refund;

use App\DTOs\Refund\CreateRefundData;
use App\DTOs\Refund\DecideRefundData;
use App\Exceptions\DuplicateRefundRequestException;
use App\Exceptions\IneligibleRefundOrderException;
use App\Exceptions\InvalidRefundStatusException;
use App\Exceptions\MissingQuoteException;
use App\Helpers\ReferenceNumberGenerator;
use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundStatus;
use App\Models\User;
use App\Models\WalletTransactionType;
use App\Traits\AuthorizesAgencyOwnership;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundService
{
    use AuthorizesAgencyOwnership;

    private const ELIGIBLE_ORDER_STATUSES = ['approved', 'confirmed'];

    public function listForUser(User $user): Collection
    {
        $query = Refund::with(['order', 'agency', 'status', 'requestedBy', 'decidedBy'])
            ->orderByDesc('requested_at');

        if ($user->isAgency()) {
            $query->where('agency_id', $this->agencyIdFor($user));
        }

        return $query->get();
    }

    public function findForUser(User $user, Refund $refund): Refund
    {
        $this->assertOwnsResource($user, $refund->agency_id);

        return $refund->load([
            'order.segments.airline',
            'order.segments.departureAirport',
            'order.segments.arrivalAirport',
            'agency',
            'status',
            'requestedBy',
            'decidedBy',
        ]);
    }

    public function create(User $user, CreateRefundData $data): Refund
    {
        $agencyId = $this->agencyIdFor($user);
        $order = Order::with(['status', 'latestQuote'])->findOrFail($data->orderId);

        $this->assertOwnsResource($user, $order->agency_id);

        if (!in_array($order->status->name, self::ELIGIBLE_ORDER_STATUSES, true)) {
            throw new IneligibleRefundOrderException();
        }

        if (Refund::where('order_id', $order->id)->whereHas('status', fn ($q) => $q->whereIn('name', ['pending', 'approved', 'credited']))->exists()) {
            throw new DuplicateRefundRequestException();
        }

        $quote = $order->latestQuote;
        if (!$quote) {
            throw new MissingQuoteException('لا يوجد عرض سعر مرتبط بهذا الطلب');
        }

        $pendingStatus = RefundStatus::where('name', 'pending')->firstOrFail();

        $refund = Refund::create([
            'refund_number' => ReferenceNumberGenerator::make('REF'),
            'order_id' => $order->id,
            'agency_id' => $agencyId,
            'status_id' => $pendingStatus->id,
            'original_amount' => $quote->total_price,
            'penalty_amount' => 0,
            'refund_amount' => $quote->total_price,
            'currency_code' => $quote->currency_code,
            'reason' => $data->reason,
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);

        return $refund->load(['order', 'status']);
    }

    public function approve(Refund $refund, DecideRefundData $data): Refund
    {
        if ($refund->status->name !== 'pending') {
            throw new InvalidRefundStatusException();
        }

        $refundAmount = max(0, $refund->original_amount - $data->penaltyAmount);

        return DB::transaction(function () use ($refund, $data, $refundAmount) {
            $creditedStatus = RefundStatus::where('name', 'credited')->firstOrFail();

            $refund->update([
                'status_id' => $creditedStatus->id,
                'penalty_amount' => $data->penaltyAmount,
                'refund_amount' => $refundAmount,
                'admin_remarks' => $data->adminRemarks,
                'decided_by' => auth()->id(),
                'decided_at' => now(),
            ]);

            $order = $refund->order;
            $order->transitionTo('cancelled', "تم استرداد الحجز رقم {$refund->refund_number}");

            $wallet = $refund->agency->wallet;
            $newBalance = $wallet->balance + $refundAmount;

            $creditType = WalletTransactionType::firstOrCreate(['name' => 'refund_credit'], ['label' => 'Refund Credit']);
            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $creditType->id,
                'amount' => $refundAmount,
                'balance_after' => $newBalance,
                'reference_type' => Refund::class,
                'reference_id' => $refund->id,
                'description' => "Refund credit for order {$order->order_number}",
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            $wallet->update(['balance' => $newBalance]);

            return $refund->fresh(['status', 'order']);
        });
    }

    public function creditNotePdf(User $user, Refund $refund)
    {
        $this->assertOwnsResource($user, $refund->agency_id);

        if ($refund->status->name !== 'credited') {
            throw new InvalidRefundStatusException('سند الاسترداد غير متاح إلا بعد اعتماد الطلب');
        }

        $refund->load(['order', 'agency', 'status', 'decidedBy']);

        return Pdf::loadView('reports.credit-note', ['refund' => $refund])
            ->download("credit-note-{$refund->refund_number}.pdf");
    }

    public function reject(Refund $refund, string $adminRemarks): Refund
    {
        if ($refund->status->name !== 'pending') {
            throw new InvalidRefundStatusException();
        }

        $rejectedStatus = RefundStatus::where('name', 'rejected')->firstOrFail();
        $refund->update([
            'status_id' => $rejectedStatus->id,
            'admin_remarks' => $adminRemarks,
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        return $refund->fresh(['status']);
    }
}
