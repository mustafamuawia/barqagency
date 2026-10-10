<?php

namespace App\Services\Order;

use App\DTOs\Order\BookItineraryData;
use App\DTOs\Order\CreateOrderData;
use App\DTOs\Order\SubmitQuoteData;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidOrderStatusException;
use App\Exceptions\MissingAgencyException;
use App\Exceptions\MissingQuoteException;
use App\Exceptions\MissingTicketException;
use App\Helpers\ReferenceNumberGenerator;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\QuoteStatus;
use App\Models\User;
use App\Models\WalletTransactionType;
use App\Notifications\Admin\CreditLimitReachedNotification as AdminCreditLimitReachedNotification;
use App\Notifications\Admin\NewOrderRequestNotification;
use App\Notifications\Admin\OrderApprovedNotification;
use App\Notifications\CreditLimitReachedNotification;
use App\Notifications\QuoteUpdatedNotification;
use App\Notifications\TicketUploadedNotification;
use App\Traits\AuthorizesAgencyOwnership;
use App\Services\Audit\AuditLogger;
use App\Services\FlightSearch\ReferenceResolver;
use App\Services\Notification\AdminNotifier;
use App\Services\Notification\AgencyNotifier;
use App\Services\Settings\PointSettingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderService
{
    use AuthorizesAgencyOwnership;

    /** The admin may still change the offer until the agency has acted on it. */
    private const QUOTE_EDITABLE_STATUSES = ['pending_pricing', 'pricing_sent'];

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly AdminNotifier $adminNotifier,
        private readonly AgencyNotifier $agencyNotifier,
        private readonly ReferenceResolver $referenceResolver,
        private readonly PointSettingService $pointSetting,
    ) {}

    public function listForUser(User $user, ?int $agencyId = null): LengthAwarePaginator
    {
        $query = Order::with(['agency', 'status', 'segments', 'passengers', 'latestQuote'])
            ->orderByDesc('created_at');

        if ($user->isAgency()) {
            $query->where('agency_id', $this->agencyIdFor($user));
        } elseif ($agencyId) {
            $query->where('agency_id', $agencyId);
        }

        return $query->paginate(20);
    }

    public function findForUser(User $user, Order $order): Order
    {
        $this->assertOwnsResource($user, $order->agency_id);

        return $order->load([
            'agency.agencyType',
            'status',
            'segments.airline',
            'segments.departureAirport.city',
            'segments.departureAirport.country',
            'segments.arrivalAirport.city',
            'segments.arrivalAirport.country',
            'segments.cabinClass',
            'passengers.passengerType',
            'quotes.status',
            'quotes.createdBy',
            'statusHistories.fromStatus',
            'statusHistories.toStatus',
            'statusHistories.createdBy',
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
                'total_passengers' => $data->totalPassengers(),
                'trip_type' => $data->tripType,
                'departure_date' => $data->departureDate,
                'return_date' => $data->returnDate,
                'created_by' => auth()->id(),
            ]);

            foreach ($data->segments as $segment) {
                $order->segments()->create($segment);
            }

            // One seat per head, typed but unnamed: the admin prices off the mix, and the
            // agency fills the traveller details in when it approves the quote.
            $sequence = 1;
            foreach ($data->passengerCounts as $entry) {
                for ($i = 0; $i < $entry['count']; $i++) {
                    $order->passengers()->create([
                        'passenger_type_id' => $entry['passenger_type_id'],
                        'sequence' => $sequence++,
                    ]);
                }
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
    /**
     * Prices an order, or revises a quote the agency has not acted on yet. Each save adds a
     * new quote so the price history stays intact; a revision also tells the agency.
     */
    public function submitQuote(Order $order, SubmitQuoteData $data): array
    {
        if (!in_array($order->status->name, self::QUOTE_EDITABLE_STATUSES, true)) {
            throw new InvalidOrderStatusException('لا يمكن تعديل العرض بعد موافقة الوكيل على الطلب');
        }

        $isRevision = $order->status->name === 'pricing_sent';

        return DB::transaction(function () use ($order, $data, $isRevision) {
            $this->applyDepartureDetails($order, $data);

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

            // Already 'pricing_sent' on a revision — this leaves the status alone and just
            // records why it changed hands again.
            $order->transitionTo(
                'pricing_sent',
                $isRevision ? 'تم تعديل عرض السعر من الإدارة' : 'تم إرسال عرض السعر من الإدارة'
            );

            $this->audit->log($isRevision ? 'order.quote_updated' : 'order.quote_submitted', $order, [], [
                'total_price' => $data->totalPrice,
                'order_number' => $order->order_number,
            ]);

            if ($isRevision) {
                $order->loadMissing('agency');
                $this->agencyNotifier->send($order->agency, new QuoteUpdatedNotification($order));
            }

            return [
                'order' => $order->fresh(['status']),
                'quote' => $order->latestQuote,
            ];
        });
    }

    /**
     * Records the departure time and hall the admin confirmed for each flight, plus the
     * return date and time on a round trip. Fields left blank are not overwritten.
     */
    private function applyDepartureDetails(Order $order, SubmitQuoteData $data): void
    {
        if ($data->flights !== []) {
            $segments = $order->segments()->get()->keyBy('id');
            $nextSequence = ($segments->max('sequence') ?? 0) + 1;
            $defaultCabinClassId = $segments->first()?->cabin_class_id;

            foreach ($data->flights as $flight) {
                $segment = isset($flight['id']) ? $segments->get($flight['id']) : null;

                if (!$segment) {
                    $order->segments()->create([
                        'airline_id' => $this->resolveFlightAirlineId($flight),
                        'departure_airport_name' => $flight['departure_airport_name'],
                        'arrival_airport_name' => $flight['arrival_airport_name'],
                        'cabin_class_id' => $defaultCabinClassId,
                        'departure_datetime' => $flight['departure_datetime'] ?? null,
                        'departure_terminal' => $flight['departure_terminal'] ?: null,
                        'sequence' => $nextSequence++,
                    ]);
                    continue;
                }

                $updates = [];

                // Only ever set a departure time, never blank an existing one.
                if (filled($flight['departure_datetime'] ?? null)) {
                    $updates['departure_datetime'] = $flight['departure_datetime'];
                }

                if (filled($flight['airline_id'] ?? null) || filled($flight['airline_name'] ?? null)) {
                    $updates['airline_id'] = $this->resolveFlightAirlineId($flight);
                }

                // The hall is optional, so an empty value is a deliberate clear.
                if (array_key_exists('departure_terminal', $flight)) {
                    $updates['departure_terminal'] = $flight['departure_terminal'] ?: null;
                }

                if ($updates !== []) {
                    $segment->update($updates);
                }
            }
        }

        if (filled($data->returnDate)) {
            $order->update(['return_date' => $data->returnDate]);
        }

        $order->load('segments');
    }

    private function resolveFlightAirlineId(array $flight): int
    {
        if (filled($flight['airline_id'] ?? null)) {
            return (int) $flight['airline_id'];
        }

        return $this->referenceResolver->findOrCreateAirlineByName($flight['airline_name'])->id;
    }

    /**
     * @param array<int, array<string, mixed>> $passengerDetails traveller details keyed by
     *        the order's passenger row ids — supplied by the agency at approval time.
     */
    public function approve(User $user, Order $order, array $passengerDetails = []): Order
    {
        $this->assertOwnsResource($user, $order->agency_id);

        if ($order->status->name !== 'pricing_sent') {
            throw new InvalidOrderStatusException('الطلب ليس بانتظار الموافقة');
        }

        $quote = $order->latestQuote;
        if (!$quote) {
            throw new MissingQuoteException();
        }

        try {
            return DB::transaction(function () use ($order, $quote, $user, $passengerDetails) {
                $this->savePassengerDetails($order, $passengerDetails);

                // Lock the wallet row to prevent concurrent debits racing past the credit limit
                $wallet = \App\Models\Wallet::where('agency_id', $order->agency_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Order pricing (quote->total_price) is in money — the wallet ledger is in points, so
                // the debit is converted at approval time, same as wallet top-ups/deposits.
                $moneyDebit = (float) $quote->total_price;
                $pointsDebit = $this->pointSetting->moneyToPoints($moneyDebit);
                $newBalance = $wallet->balance - $pointsDebit;
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
                    'amount' => -$pointsDebit,
                    'money_equivalent' => -$moneyDebit,
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
        } catch (InsufficientWalletBalanceException $e) {
            // Notified outside the transaction — it was rolled back, so any database
            // notification created inside it would be rolled back too.
            $order->loadMissing('agency');
            $this->adminNotifier->send(new AdminCreditLimitReachedNotification($order));
            if ($order->agency) {
                $this->agencyNotifier->send($order->agency, new CreditLimitReachedNotification($order));
            }

            throw $e;
        }
    }

    /**
     * Writes the traveller details the agency entered on the approval screen onto the
     * passenger rows the order was created with, then refuses to go any further while a
     * passenger is still missing a name or passport — the ticket cannot be issued without it.
     *
     * The passenger type stays as requested: it is what the admin priced the order against.
     *
     * @param array<int, array<string, mixed>> $passengerDetails
     */
    private function savePassengerDetails(Order $order, array $passengerDetails): void
    {
        $passengers = $order->passengers()->get()->keyBy('id');

        foreach ($passengerDetails as $details) {
            $passenger = $passengers->get($details['id']);

            if (!$passenger) {
                throw ValidationException::withMessages([
                    'passengers' => 'بيانات الركاب لا تطابق ركاب هذا الطلب.',
                ]);
            }

            $passenger->update([
                'title' => $details['title'] ?? null,
                'first_name' => $details['first_name'],
                'last_name' => $details['last_name'],
                'date_of_birth' => $details['date_of_birth'],
                'nationality' => $details['nationality'],
                'passport_number' => $details['passport_number'],
                'passport_expiry' => $details['passport_expiry'],
            ]);
        }

        $incomplete = $passengers->filter(
            fn ($passenger) => blank($passenger->first_name)
                || blank($passenger->last_name)
                || blank($passenger->passport_number)
        );

        if ($incomplete->isNotEmpty()) {
            throw ValidationException::withMessages([
                'passengers' => 'يجب إدخال بيانات جميع الركاب قبل تأكيد الحجز.',
            ]);
        }

        $order->load('passengers.passengerType');
    }

    /**
     * Attaches the issued ticket PDF once the agency has approved the order, and confirms
     * the booking. The admin may re-upload to replace a ticket already on a confirmed order.
     */
    public function uploadTicket(Order $order, UploadedFile $file): Order
    {
        if (!$order->canHaveTicketUploaded()) {
            throw new InvalidOrderStatusException('لا يمكن رفع التذكرة قبل موافقة الوكالة على الطلب');
        }

        $isReplacement = $order->status->name === 'confirmed';

        return DB::transaction(function () use ($order, $file, $isReplacement) {
            $path = $file->storeAs("tickets/{$order->agency_id}/{$order->order_number}", 'ticket.pdf', 'local');

            $order->update([
                'ticket_path' => $path,
                'ticket_uploaded_at' => now(),
            ]);

            $order->transitionTo('confirmed', $isReplacement ? 'تم تحديث تذكرة السفر' : 'تم رفع تذكرة السفر وتأكيد الحجز');

            $this->audit->log('order.ticket_uploaded', $order, [], ['order_number' => $order->order_number]);

            $order->loadMissing('agency');
            $this->agencyNotifier->send($order->agency, new TicketUploadedNotification($order));

            return $order->fresh(['status']);
        });
    }

    public function downloadTicket(User $user, Order $order): StreamedResponse
    {
        $this->assertOwnsResource($user, $order->agency_id);

        if (!$order->ticket_path) {
            throw new MissingTicketException();
        }

        return Storage::disk('local')->download($order->ticket_path, "ticket-{$order->order_number}.pdf");
    }
}
