<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Refund;
use App\Models\RefundStatus;
use App\Models\WalletTransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RefundController extends Controller
{
    private const ELIGIBLE_ORDER_STATUSES = ['approved', 'confirmed'];

    public function index(Request $request)
    {
        $query = Refund::with(['order', 'agency', 'status', 'requestedBy', 'decidedBy'])
            ->orderByDesc('requested_at');

        $user = $request->user();
        if ($user->isAgency()) {
            $agency = $user->agencies()->first();
            $query->where('agency_id', $agency?->id);
        }

        return response()->json($query->get());
    }

    public function show(Request $request, Refund $refund)
    {
        $user = $request->user();
        if ($user->isAgency()) {
            $agency = $user->agencies()->first();
            if ($refund->agency_id !== $agency?->id) {
                abort(403);
            }
        }

        return response()->json(
            $refund->load([
                'order.segments.airline',
                'order.segments.departureAirport',
                'order.segments.arrivalAirport',
                'agency',
                'status',
                'requestedBy',
                'decidedBy',
            ])
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string|max:1000',
        ]);

        $user = $request->user();
        $agency = $user->agencies()->first();
        $order = Order::with(['status', 'latestQuote'])->findOrFail($validated['order_id']);

        if ($order->agency_id !== $agency?->id) {
            abort(403);
        }

        if (!in_array($order->status->name, self::ELIGIBLE_ORDER_STATUSES, true)) {
            return response()->json(['message' => 'هذا الطلب غير مؤهل لطلب استرداد'], 422);
        }

        if (Refund::where('order_id', $order->id)->whereHas('status', fn ($q) => $q->whereIn('name', ['pending', 'approved', 'credited']))->exists()) {
            return response()->json(['message' => 'يوجد بالفعل طلب استرداد نشط لهذا الحجز'], 422);
        }

        $quote = $order->latestQuote;
        if (!$quote) {
            return response()->json(['message' => 'لا يوجد عرض سعر مرتبط بهذا الطلب'], 422);
        }

        $pendingStatus = RefundStatus::where('name', 'pending')->firstOrFail();

        $refund = Refund::create([
            'refund_number' => 'REF-' . now()->format('dmyH') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'order_id' => $order->id,
            'agency_id' => $agency->id,
            'status_id' => $pendingStatus->id,
            'original_amount' => $quote->total_price,
            'penalty_amount' => 0,
            'refund_amount' => $quote->total_price,
            'currency_code' => $quote->currency_code,
            'reason' => $validated['reason'],
            'requested_by' => $user->id,
            'requested_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الاسترداد',
            'refund' => $refund->load(['order', 'status']),
        ], 201);
    }

    public function approve(Request $request, Refund $refund)
    {
        $validated = $request->validate([
            'penalty_amount' => 'nullable|numeric|min:0',
            'admin_remarks' => 'nullable|string|max:1000',
        ]);

        if ($refund->status->name !== 'pending') {
            return response()->json(['message' => 'لا يمكن معالجة هذا الطلب في حالته الحالية'], 422);
        }

        $penaltyAmount = $validated['penalty_amount'] ?? 0;
        $refundAmount = max(0, $refund->original_amount - $penaltyAmount);

        $refund = DB::transaction(function () use ($refund, $penaltyAmount, $refundAmount, $validated) {
            $creditedStatus = RefundStatus::where('name', 'credited')->firstOrFail();

            $refund->update([
                'status_id' => $creditedStatus->id,
                'penalty_amount' => $penaltyAmount,
                'refund_amount' => $refundAmount,
                'admin_remarks' => $validated['admin_remarks'] ?? null,
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

            return $refund;
        });

        return response()->json([
            'message' => 'تمت الموافقة على الاسترداد وإضافة المبلغ للمحفظة',
            'refund' => $refund->fresh(['status', 'order']),
        ]);
    }

    public function reject(Request $request, Refund $refund)
    {
        $validated = $request->validate([
            'admin_remarks' => 'required|string|max:1000',
        ]);

        if ($refund->status->name !== 'pending') {
            return response()->json(['message' => 'لا يمكن معالجة هذا الطلب في حالته الحالية'], 422);
        }

        $rejectedStatus = RefundStatus::where('name', 'rejected')->firstOrFail();
        $refund->update([
            'status_id' => $rejectedStatus->id,
            'admin_remarks' => $validated['admin_remarks'],
            'decided_by' => auth()->id(),
            'decided_at' => now(),
        ]);

        return response()->json([
            'message' => 'تم رفض طلب الاسترداد',
            'refund' => $refund->fresh(['status']),
        ]);
    }
}
