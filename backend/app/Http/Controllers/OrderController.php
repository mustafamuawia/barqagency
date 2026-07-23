<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

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
                'quotes',
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

        $status = \App\Models\OrderStatus::where('name', 'pending_pricing')->first();
        $orderNumber = 'ORD-' . now()->format('dmyH') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

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

        $quoteStatus = \App\Models\QuoteStatus::where('name', 'sent')->first();
        $quote = $order->quotes()->create([
            'status_id' => $quoteStatus->id,
            'base_price' => $validated['base_price'],
            'taxes' => $validated['taxes'] ?? 0,
            'fees' => $validated['fees'] ?? 0,
            'total_price' => $validated['total_price'],
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Transition order to pricing_sent
        $pricingSentStatus = \App\Models\OrderStatus::where('name', 'pricing_sent')->first();
        $order->update(['status_id' => $pricingSentStatus->id]);

        return response()->json([
            'message' => 'Quote submitted',
            'quote' => $quote,
            'order' => $order->fresh(['status']),
        ]);
    }

    public function approveOrder(Request $request, Order $order)
    {
        $quote = $order->latestQuote;
        if (!$quote) {
            return response()->json(['message' => 'No quote available'], 422);
        }

        // Check credit limit
        $wallet = $order->agency->wallet;
        $newBalance = $wallet->balance - $quote->total_price;
        if ($newBalance < -$wallet->credit_limit) {
            return response()->json(['message' => 'Insufficient credit limit'], 422);
        }

        // Transition to approved
        $approvedStatus = \App\Models\OrderStatus::where('name', 'approved')->first();
        $order->update(['status_id' => $approvedStatus->id]);

        // Debit wallet
        $wallet->update(['balance' => $newBalance]);

        return response()->json([
            'message' => 'Order approved',
            'order' => $order->fresh(['status']),
        ]);
    }
}
