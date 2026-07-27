<?php

namespace App\Services\Order;

use App\DTOs\Order\BookItineraryData;
use App\DTOs\Order\CreateOrderData;
use App\DTOs\Order\SubmitQuoteData;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidOrderStatusException;
use App\Exceptions\MissingAgencyException;
use App\Exceptions\MissingQuoteException;
use App\Helpers\ReferenceNumberGenerator;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Models\WalletTransactionType;
use App\Notifications\Admin\NewOrderRequestNotification;
use App\Notifications\Admin\OrderApprovedNotification;
use App\Traits\AuthorizesAgencyOwnership;
use App\Services\Audit\AuditLogger;
use App\Services\FlightSearch\ReferenceResolver;
use App\Services\Notification\AdminNotifier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    use AuthorizesAgencyOwnership;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AdminNotifier $adminNotifier,
        private readonly ReferenceResolver $referenceResolver,
    ) {}

    public function listForUser(User $user): LengthAwarePaginator
    {
        $query = Order::with(['agency', 'status', 'segments', 'passengers', 'latestQuote'])
            ->orderByDesc('created_at');

        if ($user->isAgency()) {
            $query->where('agency_id', $this->agencyIdFor($user));
        }

        return $query->paginate(20);
    }

    public function findForUser(User $user, Order $order): Order
    {
        $this->assertOwnsResource($user, $order->agency_id);

        return $order->load([
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
        ]);
    }

    public function create(User $user, CreateOrderData $data): Order
    {
        $agencyId = $data->agencyId;
        if ($user->isAgency()) {
            $agencyId = $this->agencyIdFor($user);
        }

        if (!$agencyId) {
            throw new MissingAgencyException('No agency specified');
        }

        $status = OrderStatus::where('name', 'pending_pricing')->firstOrFail();
        $orderNumber = ReferenceNumberGenerator::make('ORD');

        return DB::transaction(function () use ($data, $agencyId, $status, $orderNumber) {
            $order = Order::create([
                'order_number' => $orderNumber,
                'agency_id' => $agencyId,
                'status_id' => $status->id,
                'total_passengers' => $data->totalPassengers,
                'departure_date' => $data->departureDate,
                'return_date' => $data->returnDate,
                'created_by' => auth()->id(),
            ]);

            foreach ($data->segments as $segment) {
                $order->segments()->create($segment);
            }

            foreach ($data->passengers as $passenger) {
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

            $order->load(['agency', 'status', 'segments', 'passengers']);

            $this->adminNotifier->send(new NewOrderRequestNotification($order));

            return $order;
        });
    }

    /**
     * Creates an order pre-priced from a live Ignav search result — skips the
     * manual admin pricing step since the itinerary was already quoted by Ignav.
     */
    public function bookItinerary(User $user, BookItineraryData $data): Order
    {
        $agencyId = $this->agencyIdFor($user);
        if (!$agencyId) {
            throw new MissingAgencyException('No agency specified');
        }

        $pendingStatus = OrderStatus::where('name', 'pending_pricing')->firstOrFail();
        $orderNumber = ReferenceNumberGenerator::make('ORD');
        $itinerary = $data->itinerary;

        return DB::transaction(function () use ($data, $agencyId, $pendingStatus, $orderNumber, $itinerary) {
            $order = Order::create([
                'order_number' => $orderNumber,
                'agency_id' => $agencyId,
                'status_id' => $pendingStatus->id,
                'estimated_price' => $itinerary['total_price'],
                'total_passengers' => count($data->passengers),
                'departure_date' => $data->departureDate,
                'return_date' => $data->returnDate,
                'itinerary_details' => $itinerary,
                'metadata' => ['source' => 'ignav', 'ignav_id' => $itinerary['ignav_id'] ?? null],
                'created_by' => auth()->id(),
            ]);

            $cabinClass = $this->referenceResolver->mapCabinClass($itinerary['cabin_class']);

            $sequence = 1;
            foreach (['outbound', 'inbound'] as $leg) {
                if (empty($itinerary[$leg]['segments'])) {
                    continue;
                }

                foreach ($itinerary[$leg]['segments'] as $segment) {
                    $airline = $this->referenceResolver->findOrCreateAirline(
                        $segment['marketing_carrier_code'],
                        $segment['operating_carrier_name'] ?? $itinerary[$leg]['carrier'] ?? null
                    );

                    $order->segments()->create([
                        'airline_id' => $airline->id,
                        'departure_airport_id' => $this->referenceResolver->findOrCreateAirport($segment['departure_airport'])->id,
                        'arrival_airport_id' => $this->referenceResolver->findOrCreateAirport($segment['arrival_airport'])->id,
                        'cabin_class_id' => $cabinClass->id,
                        'departure_datetime' => $segment['departure_time_local'],
                        'arrival_datetime' => $segment['arrival_time_local'],
                        'flight_number' => $segment['flight_number'] ?? null,
                        'aircraft_type' => $segment['aircraft'] ?? null,
                        'duration_minutes' => $segment['duration_minutes'] ?? null,
                        'sequence' => $sequence++,
                        'metadata' => ['leg' => $leg],
                    ]);
                }
            }

            foreach ($data->passengers as $index => $passenger) {
                $order->passengers()->create([
                    'passenger_type_id' => $passenger['passenger_type_id'],
                    'first_name' => $passenger['first_name'],
                    'last_name' => $passenger['last_name'],
                    'sequence' => $index + 1,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status_id' => null,
                'to_status_id' => $pendingStatus->id,
                'reason' => 'تم إنشاء الطلب من نتيجة بحث Ignav',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $quoteStatus = QuoteStatus::where('name', 'sent')->firstOrFail();
            $order->quotes()->create([
                'status_id' => $quoteStatus->id,
                'base_price' => $itinerary['total_price'],
                'taxes' => 0,
                'fees' => 0,
                'total_price' => $itinerary['total_price'],
                'currency_code' => $itinerary['currency'],
                'remarks' => 'تم التسعير تلقائيًا بناءً على نتيجة بحث Ignav',
                'created_by' => auth()->id(),
            ]);

            $order->transitionTo('pricing_sent', 'تم التسعير التلقائي عبر خدمة Ignav');

            $this->audit->log('order.quote_submitted', $order, [], [
                'total_price' => $itinerary['total_price'],
                'order_number' => $order->order_number,
                'source' => 'ignav',
            ]);

            $order->load(['agency', 'status', 'segments', 'passengers']);

            $this->adminNotifier->send(new NewOrderRequestNotification($order));

            return $order;
        });
    }

    /**
     * @return array{order: Order, quote: mixed}
     */
    public function submitQuote(Order $order, SubmitQuoteData $data): array
    {
        if ($order->status->name !== 'pending_pricing') {
            throw new InvalidOrderStatusException('الطلب ليس بانتظار التسعير');
        }

        return DB::transaction(function () use ($order, $data) {
            $quoteStatus = QuoteStatus::where('name', 'sent')->firstOrFail();
            $order->quotes()->create([
                'status_id' => $quoteStatus->id,
                'base_price' => $data->basePrice,
                'taxes' => $data->taxes,
                'fees' => $data->fees,
                'total_price' => $data->totalPrice,
                'remarks' => $data->remarks,
                'created_by' => auth()->id(),
            ]);

            $order->transitionTo('pricing_sent', 'تم إرسال عرض السعر من الإدارة');

            $this->audit->log('order.quote_submitted', $order, [], [
                'total_price' => $data->totalPrice,
                'order_number' => $order->order_number,
            ]);

            return [
                'order' => $order->fresh(['status']),
                'quote' => $order->latestQuote,
            ];
        });
    }

    public function approve(User $user, Order $order): Order
    {
        $this->assertOwnsResource($user, $order->agency_id);

        if ($order->status->name !== 'pricing_sent') {
            throw new InvalidOrderStatusException('الطلب ليس بانتظار الموافقة');
        }

        $quote = $order->latestQuote;
        if (!$quote) {
            throw new MissingQuoteException();
        }

        return DB::transaction(function () use ($order, $quote, $user) {
            // Lock the wallet row to prevent concurrent debits racing past the credit limit
            $wallet = \App\Models\Wallet::where('agency_id', $order->agency_id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBalance = $wallet->balance - $quote->total_price;
            if ($newBalance < -$wallet->credit_limit) {
                throw new InsufficientWalletBalanceException();
            }

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

            $this->audit->log('order.approved', $order, [
                'status' => 'pricing_sent',
                'balance_before' => (float) $wallet->getOriginal('balance'),
            ], [
                'status' => 'approved',
                'amount_debited' => (float) $quote->total_price,
                'balance_after' => $newBalance,
            ]);

            $order = $order->fresh(['status', 'agency']);
            $this->adminNotifier->send(new OrderApprovedNotification($order));

            return $order;
        });
    }
}
