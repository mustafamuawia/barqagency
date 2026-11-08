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
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransactionType;
use App\Notifications\Admin\NewRefundRequestNotification;
use App\Notifications\RefundApprovedNotification;
use App\Notifications\RefundRejectedNotification;
use App\Services\Audit\AuditLogger;
use App\Services\FlightSearch\DuffelOrderService;
use App\Services\Notification\AdminNotifier;
use App\Services\Notification\AgencyNotifier;
use App\Services\Pdf\PdfRenderer;
use App\Services\Settings\PointSettingService;
use App\Traits\AuthorizesAgencyOwnership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundService
{
    use AuthorizesAgencyOwnership;

    private const ELIGIBLE_ORDER_STATUSES = ['approved', 'confirmed'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PdfRenderer $pdfRenderer,
        private readonly AdminNotifier $adminNotifier,
        private readonly AgencyNotifier $agencyNotifier,
        private readonly PointSettingService $pointSetting,
        private readonly DuffelOrderService $duffelOrderService,
    ) {
    }

    public function listForUser(User $user): LengthAwarePaginator
    {
        $query = Refund::with(['order', 'agency', 'status', 'requestedBy', 'decidedBy'])
            ->orderByDesc('requested_at');

        if ($user->isAgency()) {
            $query->where('agency_id', $this->agencyIdFor($user));
        }

        return $query->paginate(20);
    }

    public function pendingCountForUser(User $user): int
    {
        $query = Refund::whereHas('status', fn ($q) => $q->where('name', 'pending'));

        if ($user->isAgency()) {
            $query->where('agency_id', $this->agencyIdFor($user));
        }

        return $query->count();
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

        $isDuffel = ($order->metadata['source'] ?? null) === 'duffel';

        if ($isDuffel) {
            // Duffel orders have no admin quote — the original amount is what was debited from the wallet.
            $originalAmount = (float) ($order->metadata['paid_sar'] ?? 0);
            $currencyCode   = 'SAR';
        } else {
            $quote = $order->latestQuote;
            if (!$quote) {
                throw new MissingQuoteException('لا يوجد عرض سعر مرتبط بهذا الطلب');
            }
            $originalAmount = (float) $quote->total_price;
            $currencyCode   = $quote->currency_code ?? 'USD';
        }

        $pendingStatus = RefundStatus::where('name', 'pending')->firstOrFail();

        $refund = Refund::create([
            'refund_number'  => ReferenceNumberGenerator::make('REF'),
            'order_id'       => $order->id,
            'agency_id'      => $agencyId,
            'status_id'      => $pendingStatus->id,
            'original_amount' => $originalAmount,
            'penalty_amount'  => 0,
            'refund_amount'   => $originalAmount,
            'currency_code'   => $currencyCode,
            'reason'          => $data->reason,
            'requested_by'    => $user->id,
            'requested_at'    => now(),
        ]);

        $this->audit->log('refund.requested', $refund, [], [
            'refund_number'  => $refund->refund_number,
            'original_amount' => $originalAmount,
            'source'          => $isDuffel ? 'duffel' : 'manual',
        ]);

        $refund->load(['order', 'status', 'agency']);
        $this->adminNotifier->send(new NewRefundRequestNotification($refund));

        return $refund;
    }

    public function approve(Refund $refund, DecideRefundData $data): Refund
    {
        if ($refund->status->name !== 'pending') {
            throw new InvalidRefundStatusException();
        }

        $order    = $refund->order;
        $isDuffel = ($order->metadata['source'] ?? null) === 'duffel';

        $result = DB::transaction(function () use ($refund, $data, $order, $isDuffel) {
            $creditedStatus = RefundStatus::where('name', 'credited')->firstOrFail();

            if ($isDuffel) {
                // Cancel with the airline via Duffel — the actual refund amount comes from their policy.
                // If force=true the admin bypasses the API (e.g. airline rejected cancel) and sets the amount manually.
                if ($data->force) {
                    $refundedSar = $data->manualRefundAmount ?? 0.0;
                } else {
                    $refundedSar = $this->duffelOrderService->cancelForRefund($order);
                }
                $penaltyAmt  = max(0, (float) $refund->original_amount - $refundedSar);

                $refund->update([
                    'status_id'      => $creditedStatus->id,
                    'penalty_amount' => $penaltyAmt,
                    'refund_amount'  => $refundedSar,
                    'admin_remarks'  => $data->adminRemarks,
                    'decided_by'     => auth()->id(),
                    'decided_at'     => now(),
                ]);

                $order->transitionTo('cancelled', "تم استرداد الحجز المباشر رقم {$refund->refund_number}");

                $wallet       = Wallet::where('agency_id', $refund->agency_id)->lockForUpdate()->firstOrFail();
                $balanceBefore = (float) $wallet->balance;
                $newBalance   = $balanceBefore + $refundedSar;

                $creditType = WalletTransactionType::firstOrCreate(['name' => 'refund_credit'], ['label' => 'Refund Credit']);
                $wallet->transactions()->create([
                    'uuid'                => Str::uuid(),
                    'transaction_type_id' => $creditType->id,
                    'amount'              => $refundedSar,
                    'money_equivalent'    => $refundedSar,
                    'balance_after'       => $newBalance,
                    'reference_type'      => Refund::class,
                    'reference_id'        => $refund->id,
                    'description'         => "Refund credit for direct booking {$order->order_number}",
                    'created_by'          => auth()->id(),
                    'created_at'          => now(),
                ]);
                $wallet->update(['balance' => $newBalance]);

                $this->audit->log('refund.approved', $refund, [
                    'status'         => 'pending',
                    'balance_before' => $balanceBefore,
                ], [
                    'status'         => 'credited',
                    'refund_amount'  => $refundedSar,
                    'penalty_amount' => $penaltyAmt,
                    'balance_after'  => (float) $newBalance,
                    'source'         => 'duffel',
                ]);
            } else {
                $refundAmount = max(0, $refund->original_amount - $data->penaltyAmount);

                $refund->update([
                    'status_id'      => $creditedStatus->id,
                    'penalty_amount' => $data->penaltyAmount,
                    'refund_amount'  => $refundAmount,
                    'admin_remarks'  => $data->adminRemarks,
                    'decided_by'     => auth()->id(),
                    'decided_at'     => now(),
                ]);

                $order->transitionTo('cancelled', "تم استرداد الحجز رقم {$refund->refund_number}");

                $wallet       = Wallet::where('agency_id', $refund->agency_id)->lockForUpdate()->firstOrFail();
                $balanceBefore = (float) $wallet->balance;
                $pointsCredit = $this->pointSetting->moneyToPoints((float) $refundAmount);
                $newBalance   = $wallet->balance + $pointsCredit;

                $creditType = WalletTransactionType::firstOrCreate(['name' => 'refund_credit'], ['label' => 'Refund Credit']);
                $wallet->transactions()->create([
                    'uuid'                => Str::uuid(),
                    'transaction_type_id' => $creditType->id,
                    'amount'              => $pointsCredit,
                    'money_equivalent'    => (float) $refundAmount,
                    'balance_after'       => $newBalance,
                    'reference_type'      => Refund::class,
                    'reference_id'        => $refund->id,
                    'description'         => "Refund credit for order {$order->order_number}",
                    'created_by'          => auth()->id(),
                    'created_at'          => now(),
                ]);
                $wallet->update(['balance' => $newBalance]);

                $this->audit->log('refund.approved', $refund, [
                    'status'         => 'pending',
                    'balance_before' => $balanceBefore,
                ], [
                    'status'         => 'credited',
                    'refund_amount'  => (float) $refundAmount,
                    'penalty_amount' => (float) $data->penaltyAmount,
                    'balance_after'  => (float) $newBalance,
                ]);
            }

            return $refund->fresh(['status', 'order', 'agency']);
        });

        // Notify agency outside the transaction to avoid holding the lock during I/O.
        $agency = TravelAgency::find($refund->agency_id);
        if ($agency) {
            $this->agencyNotifier->send($agency, new RefundApprovedNotification($result));
        }

        return $result;
    }

    public function creditNotePdf(User $user, Refund $refund)
    {
        $this->assertOwnsResource($user, $refund->agency_id);

        if ($refund->status->name !== 'credited') {
            throw new InvalidRefundStatusException('سند الاسترداد غير متاح إلا بعد اعتماد الطلب');
        }

        $refund->load(['order', 'agency', 'status', 'decidedBy']);

        return $this->pdfRenderer->render('reports.credit-note', ['refund' => $refund], "credit-note-{$refund->refund_number}.pdf");
    }

    public function reject(Refund $refund, string $adminRemarks): Refund
    {
        if ($refund->status->name !== 'pending') {
            throw new InvalidRefundStatusException();
        }

        $rejectedStatus = RefundStatus::where('name', 'rejected')->firstOrFail();
        $refund->update([
            'status_id'      => $rejectedStatus->id,
            'admin_remarks'  => $adminRemarks,
            'decided_by'     => auth()->id(),
            'decided_at'     => now(),
        ]);

        $this->audit->log('refund.rejected', $refund, ['status' => 'pending'], ['status' => 'rejected', 'admin_remarks' => $adminRemarks]);

        $refund->load(['order', 'agency']);
        $agency = TravelAgency::find($refund->agency_id);
        if ($agency) {
            $this->agencyNotifier->send($agency, new RefundRejectedNotification($refund));
        }

        return $refund->fresh(['status']);
    }
}
