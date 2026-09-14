<?php

namespace App\Http\Controllers\Api\V1\Order;

use App\DTOs\Order\BookItineraryData;
use App\DTOs\Order\CreateOrderData;
use App\DTOs\Order\SubmitQuoteData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\StoreOrderFromItineraryRequest;
use App\Http\Requests\Api\V1\Order\StoreOrderRequest;
use App\Http\Requests\Api\V1\Order\SubmitQuoteRequest;
use App\Http\Requests\Api\V1\Order\UploadTicketRequest;
use App\Http\Resources\Api\V1\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'agency_id' => ['nullable', 'integer', 'exists:travel_agencies,id'],
        ]);

        $orders = $this->orderService->listForUser($request->user(), $validated['agency_id'] ?? null);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $order = $this->orderService->findForUser($request->user(), $order);

        return response()->json($order);
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->create($request->user(), CreateOrderData::fromRequest($request));

        return response()->json([
            'message' => 'Order created',
            'order' => new OrderResource($order),
        ], 201);
    }

    public function bookItinerary(StoreOrderFromItineraryRequest $request)
    {
        $order = $this->orderService->bookItinerary($request->user(), BookItineraryData::fromRequest($request));

        return response()->json([
            'message' => 'Order created and priced',
            'order' => new OrderResource($order),
        ], 201);
    }

    public function submitQuote(SubmitQuoteRequest $request, Order $order)
    {
        $result = $this->orderService->submitQuote($order, SubmitQuoteData::fromRequest($request));

        return response()->json([
            'message' => 'Quote submitted',
            'quote' => $result['quote'],
            'order' => new OrderResource($result['order']),
        ]);
    }

    public function approveOrder(Request $request, Order $order)
    {
        $order = $this->orderService->approve($request->user(), $order);

        return response()->json([
            'message' => 'Order approved',
            'order' => new OrderResource($order),
        ]);
    }

    public function uploadTicket(UploadTicketRequest $request, Order $order)
    {
        $this->orderService->uploadTicket($order, $request->file('ticket'));

        $order = $this->orderService->findForUser($request->user(), $order);

        return response()->json([
            'message' => 'Ticket uploaded',
            'order' => new OrderResource($order),
        ]);
    }

    public function downloadTicket(Request $request, Order $order)
    {
        return $this->orderService->downloadTicket($request->user(), $order);
    }
}
