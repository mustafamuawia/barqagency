<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\QuoteStatus;
use App\Models\WalletTransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['agency', 'status', 'segments', 'passengers', 'latestQuote'])
            ->orderByDesc('created_at');

        $user = $request->user();
        if ($user->isAgency()) {
            $agency = $user->agencies()->first();
            $query->where('agency_id', $agency?->id);
        }

        $orders = $query->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        if ($user->isAgency()) {
            $agency = $user->agencies()->first();
            if ($order->agency_id !== $agency?->id) {
                abort(403);
            }
        }

        return response()->json(
            $order->load([
                'agency',
                'status',
                'segments.airline',
                'segments.departureAirport',
                'segments.arrivalAirport',
                'segments.cabinClass',
                'passengers.passengerType',
                'quotes.status',
                'statusHistories.fromStatus',
                'statusHistories.toStatus',
            ])
        );
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'agency_id' => 'sometimes|exists:travel_agencies,id',
            'departure_date' => 'required|date|after_or_equal:today',
            'return_date' => 'nullable|date|after:departure_date',
            'total_passengers' => 'required|integer|min:1',
            'segments' => 'required|array|min:1',
            'passengers' => 'required|array|min:1',
        ]);

        $agencyId = $validated['agency_id'] ?? null;
        if ($user->isAgency()) {
            $agencyId = $user->agencies()->first()?->id;
        }

        if (!$agencyId) {
            return response()->json(['message' => 'No agency specified'], 422);
        }

        $status = OrderStatus::where('name', 'pending_pricing')->firstOrFail();
        $orderNumber = 'ORD-' . now()->format('dmyH') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $order = DB::transaction(function () use ($validated, $agencyId, $status, $orderNumber) {
            $order = Order::create([
                'order_number' => $orderNumber,
                'agency_id' => $agencyId,
                'status_id' => $status->id,
                'total_passengers' => $validated['total_passengers'],
                'departure_date' => $validated['departure_date'],
                'return_date' => $validated['return_date'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['segments'] as $segment) {
                $order->segments()->create($segment);
            }

            foreach ($validated['passengers'] as $passenger) {
                $order->passengers()->create($passenger);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status_id' => null,
                'to_status_id' => $status->id,
                'reason' => 'تم إنشاء الطلب',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            return $order;
        });

        return response()->json([
            'message' => 'Order created',
            'order' => $order->load(['agency', 'status', 'segments', 'passengers']),
        ], 201);
    }

    public function submitQuote(Request $request, Order $order)
    {
        $validated = $request->validate([
            'base_price' => 'required|numeric|min:0',
            'taxes' => 'nullable|numeric|min:0',
            'fees' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'remarks' => 'nullable|string|max:1000',
        ]);

        if ($order->status->name !== 'pending_pricing') {
            return response()->json(['message' => 'الطلب ليس بانتظار التسعير'], 422);
        }

        $order = DB::transaction(function () use ($order, $validated) {
            $quoteStatus = QuoteStatus::where('name', 'sent')->firstOrFail();
            $order->quotes()->create([
                'status_id' => $quoteStatus->id,
                'base_price' => $validated['base_price'],
                'taxes' => $validated['taxes'] ?? 0,
                'fees' => $validated['fees'] ?? 0,
                'total_price' => $validated['total_price'],
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $order->transitionTo('pricing_sent', 'تم إرسال عرض السعر من الإدارة');

            return $order;
        });

        return response()->json([
            'message' => 'Quote submitted',
            'quote' => $order->latestQuote,
            'order' => $order->fresh(['status']),
        ]);
    }

    public function approveOrder(Request $request, Order $order)
    {
        $user = $request->user();
        $agency = $user->agencies()->first();
        if ($order->agency_id !== $agency?->id) {
            abort(403);
        }

        if ($order->status->name !== 'pricing_sent') {
            return response()->json(['message' => 'الطلب ليس بانتظار الموافقة'], 422);
        }

        $quote = $order->latestQuote;
        if (!$quote) {
            return response()->json(['message' => 'No quote available'], 422);
        }

        $wallet = $order->agency->wallet;
        $newBalance = $wallet->balance - $quote->total_price;
        if ($newBalance < -$wallet->credit_limit) {
            return response()->json(['message' => 'الرصيد غير كافٍ ويتجاوز حد الائتمان المسموح'], 422);
        }

        $order = DB::transaction(function () use ($order, $quote, $wallet, $newBalance) {
            $order->transitionTo('approved', 'تمت الموافقة على العرض من قبل الوكالة');

            $acceptedStatus = QuoteStatus::where('name', 'accepted')->firstOrFail();
            $quote->update(['status_id' => $acceptedStatus->id]);

            $debitType = WalletTransactionType::firstOrCreate(['name' => 'booking_debit'], ['label' => 'Booking Debit']);
            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $debitType->id,
                'amount' => -$quote->total_price,
                'balance_after' => $newBalance,
                'reference_type' => Order::class,
                'reference_id' => $order->id,
                'description' => "Booking debit for order {$order->order_number}",
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            $wallet->update(['balance' => $newBalance]);

            return $order;
        });

        return response()->json([
            'message' => 'Order approved',
            'order' => $order->fresh(['status']),
        ]);
    }
}
