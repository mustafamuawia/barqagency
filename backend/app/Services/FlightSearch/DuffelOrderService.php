<?php

namespace App\Services\FlightSearch;

use App\Exceptions\DuffelBookingUncertainException;
use App\Exceptions\DuffelDuplicateBookingException;
use App\Exceptions\DuffelOfferExpiredException;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Helpers\ReferenceNumberGenerator;
use App\Models\DuffelBookingAttempt;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\OrderStatusHistory;
use App\Models\PassengerType;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransactionType;
use App\Services\Settings\PointSettingService;
use App\Traits\AuthorizesAgencyOwnership;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DuffelOrderService
{
    use AuthorizesAgencyOwnership;


    public function __construct(
        private readonly DuffelClient $duffel,
        private readonly ReferenceResolver $referenceResolver,
        private readonly PointSettingService $pointSettingService,
        private readonly TicketDataFormatter $ticketFormatter,
    ) {}

    public function book(User $user, array $data): array
    {
        $agencyId = $this->agencyIdFor($user);

        // H-2: One atomic lock per offer — concurrent requests for the same offer return 409.
        $lock = Cache::lock('duffel_booking_lock:' . $data['offer_id'], 90);
        if (!$lock->get()) {
            throw new DuffelDuplicateBookingException();
        }

        // H-1: Unique key for this booking attempt — guards against double-charge on timeout.
        $idempotencyKey = (string) Str::uuid();

        try {
        // --- 1. Load trusted pricing from server-side cache (written at search time) ---
        // We NEVER accept total_price_sar, duffel_total_amount, or duffel_total_currency
        // from the client. A cache miss means the offer has expired.
        $cachedOffer = Cache::get('duffel_offer:' . $data['offer_id']);
        if (!$cachedOffer) {
            throw new DuffelOfferExpiredException();
        }

        $sarAmount      = (float) $cachedOffer['sar'];
        $duffelAmount   = $cachedOffer['amount'];
        $duffelCurrency = $cachedOffer['currency'];

        // Validate that all passenger IDs in the request belong to this offer (C-2 defence
        // in depth — prevents reusing IDs from a different, cheaper offer).
        $validPassengerIds = $cachedOffer['passenger_ids'];
        foreach ($data['passengers'] as $p) {
            if (!in_array($p['duffel_passenger_id'], $validPassengerIds, true)) {
                throw new \InvalidArgumentException('معرّف المسافر غير صالح لهذا العرض');
            }
        }

        // --- 2. Pre-check wallet balance before hitting the external API ---
        $commission    = (float) ($this->pointSettingService->current()->agency_commission_amount ?? 0);
        $totalDebit    = round($sarAmount + $commission, 3);
        $wallet = Wallet::where('agency_id', $agencyId)->firstOrFail();
        if (!$wallet->canDebit($totalDebit)) {
            throw new InsufficientWalletBalanceException();
        }

        // --- 3. Build Duffel passengers array ---
        $duffelPassengers = array_map(function ($p) {
            $passenger = [
                'id'           => $p['duffel_passenger_id'],
                'title'        => $p['title'],
                'given_name'   => $p['given_name'],
                'family_name'  => $p['family_name'],
                'born_on'      => $p['born_on'],
                'email'        => $p['email'],
                'phone_number' => $p['phone_number'],
                'gender'       => $p['gender'],
            ];
            if (!empty($p['identity_documents'])) {
                $passenger['identity_documents'] = $p['identity_documents'];
            }
            return $passenger;
        }, $data['passengers']);

        // --- 4. Persist the attempt BEFORE hitting Duffel ---
        // This survives process crashes: if we die after Duffel responds but before we can
        // update our own DB, the reconciliation command will find this record and finalise it.
        $attempt = DuffelBookingAttempt::create([
            'agency_id'         => $agencyId,
            'created_by'        => $user->id,
            'offer_id'          => $data['offer_id'],
            'idempotency_key'   => $idempotencyKey,
            'duffel_passengers' => $duffelPassengers,
            'duffel_amount'     => $duffelAmount,
            'duffel_currency'   => $duffelCurrency,
            'sar_amount'        => $sarAmount,
            'status'            => 'pending',
            'last_attempted_at' => now(),
        ]);

        // --- 5. Call Duffel (outside transaction — external API) ---
        // Use the CACHED amount/currency, never any value supplied by the client.
        try {
            $duffelOrder = $this->duffel->bookOrder(
                $data['offer_id'],
                $duffelPassengers,
                $duffelCurrency,
                $duffelAmount,
                $idempotencyKey,
            );
        } catch (DuffelBookingUncertainException $e) {
            // Connection timed out — Duffel may or may not have processed the booking.
            // The attempt stays 'pending'; the reconciliation command will retry safely
            // using the same idempotency key within Duffel's 24-hour idempotency window.
            throw new DuffelBookingUncertainException($attempt->uuid);
        } catch (\Throwable $e) {
            // A known, definitive failure (e.g. offer expired, bad request).
            // Duffel did NOT create a booking, so the wallet is safe to leave untouched.
            $attempt->update(['status' => 'failed', 'resolved_at' => now()]);
            throw $e;
        }

        // Fetch the full order immediately — the initial booking response often lacks documents.
        try {
            $fullOrder = $this->duffel->getOrder($duffelOrder['id']);
            if (!empty($fullOrder['documents'])) {
                $duffelOrder['documents'] = $fullOrder['documents'];
            }
            if (!empty($fullOrder['passengers'])) {
                $duffelOrder['passengers'] = $fullOrder['passengers'];
            }
        } catch (\Throwable) {
            // Non-fatal — booking succeeded; documents can be fetched separately.
        }

        // Invalidate the cache entry so the same offer cannot be booked again.
        Cache::forget('duffel_offer:' . $data['offer_id']);

        // --- 6. Persist everything atomically ---
        $order = $this->persistBooking($duffelOrder, $agencyId, $user->id, $data, $wallet, $sarAmount, $duffelAmount, $duffelCurrency, $idempotencyKey);

        // Mark the attempt as reconciled and link it to the order.
        $attempt->update([
            'status'       => 'reconciled',
            'order_id'     => $order->id,
            'resolved_at'  => now(),
        ]);

            return [
                'local_order_id'    => $order->id,
                'order_number'      => $order->order_number,
                'duffel_order_id'   => $duffelOrder['id'],
                'booking_reference' => $duffelOrder['booking_reference'] ?? null,
                'documents'         => $this->filterRealDocuments($duffelOrder['documents'] ?? []),
                'total_amount'      => $duffelOrder['total_amount'],
                'total_currency'    => $duffelOrder['total_currency'],
                'deducted_sar'      => $totalDebit,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * Reconcile a pending booking attempt.
     *
     * Duffel's idempotency guarantee: retrying POST /air/orders with the SAME
     * Idempotency-Key within 24 hours either returns the original response (if
     * Duffel processed it) or creates the booking fresh (if Duffel never saw it).
     * Either way it is safe — no double-booking occurs.
     *
     * Outside the 24-hour window the idempotency guarantee expires and the attempt
     * is marked failed to prevent stale data from ever debiting the wallet.
     */
    public function reconcileAttempt(DuffelBookingAttempt $attempt): string
    {
        if (!$attempt->isPending()) {
            return 'skipped';
        }

        if (!$attempt->isWithinIdempotencyWindow()) {
            $attempt->update(['status' => 'failed', 'resolved_at' => now()]);
            Log::warning('Duffel booking attempt expired without reconciliation', ['uuid' => $attempt->uuid]);
            return 'expired';
        }

        $attempt->increment('attempts');
        $attempt->update(['last_attempted_at' => now()]);

        try {
            $duffelOrder = $this->duffel->bookOrder(
                $attempt->offer_id,
                $attempt->duffel_passengers,
                $attempt->duffel_currency,
                $attempt->duffel_amount,
                $attempt->idempotency_key,
            );
        } catch (DuffelBookingUncertainException) {
            // Still no connectivity — will retry on the next scheduled run.
            return 'retry';
        } catch (\Throwable $e) {
            // Definitive Duffel error; the booking never happened.
            $attempt->update(['status' => 'failed', 'resolved_at' => now()]);
            Log::error('Duffel booking attempt reconciliation failed', [
                'uuid'  => $attempt->uuid,
                'error' => $e->getMessage(),
            ]);
            return 'failed';
        }

        // Check if an order was already created for this idempotency key (handles crash-after-commit).
        $existingOrder = Order::where('metadata->idempotency_key', $attempt->idempotency_key)->first();
        if ($existingOrder) {
            $attempt->update(['status' => 'reconciled', 'order_id' => $existingOrder->id, 'resolved_at' => now()]);
            return 'already_reconciled';
        }

        $wallet = Wallet::where('agency_id', $attempt->agency_id)->firstOrFail();
        $reconCommission = (float) ($this->pointSettingService->current()->agency_commission_amount ?? 0);
        $reconTotal      = round((float) $attempt->sar_amount + $reconCommission, 3);
        if (!$wallet->canDebit($reconTotal)) {
            $attempt->update(['status' => 'failed', 'resolved_at' => now()]);
            Log::warning('Duffel reconciliation failed: insufficient balance', ['uuid' => $attempt->uuid]);
            return 'insufficient_balance';
        }

        $data = ['passengers' => $attempt->duffel_passengers];
        $order = $this->persistBooking(
            $duffelOrder,
            $attempt->agency_id,
            $attempt->created_by ?? 0,
            $data,
            $wallet,
            (float) $attempt->sar_amount,
            $attempt->duffel_amount,
            $attempt->duffel_currency,
            $attempt->idempotency_key,
        );

        $attempt->update(['status' => 'reconciled', 'order_id' => $order->id, 'resolved_at' => now()]);
        Log::info('Duffel booking attempt reconciled', ['uuid' => $attempt->uuid, 'order_id' => $order->id]);
        return 'reconciled';
    }

    public function fetchTicket(string $duffelOrderId, Order $order): array
    {
        $duffelOrder = $this->duffel->getOrder($duffelOrderId);

        return $this->ticketFormatter->format($duffelOrder, [
            'order_number' => $order->order_number,
            'status'       => $order->status->name ?? 'confirmed',
        ]);
    }

    public function cancel(User $user, Order $order): array
    {
        $duffelOrderId = $order->metadata['duffel_order_id'] ?? null;

        if (!$duffelOrderId) {
            throw new \InvalidArgumentException('هذا الطلب لم يتم حجزه عبر Duffel');
        }

        if ($order->status->name === 'cancelled') {
            throw new \InvalidArgumentException('تم إلغاء هذا الطلب مسبقاً');
        }

        $cancellation = $this->duffel->cancelOrder($duffelOrderId);

        $refundSar    = isset($cancellation['refund_amount'], $cancellation['refund_currency'])
            ? $this->toSar((float) $cancellation['refund_amount'], strtoupper($cancellation['refund_currency']))
            : 0.0;

        DB::transaction(function () use ($order, $user, $cancellation, $refundSar) {
            $order->transitionTo('cancelled', 'تم إلغاء الحجز عبر Duffel');

            if ($refundSar > 0) {
                $agencyId = $order->agency_id;
                $wallet   = Wallet::where('agency_id', $agencyId)->lockForUpdate()->firstOrFail();
                $this->creditWallet($wallet, $refundSar, $cancellation['refund_amount'], $cancellation['refund_currency'], $user->id, $order->order_number);
            }
        });

        return [
            'refund_amount'   => $cancellation['refund_amount'] ?? null,
            'refund_currency' => $cancellation['refund_currency'] ?? null,
            'refund_to'       => $cancellation['refund_to'] ?? 'balance',
            'refunded_sar'    => $refundSar,
        ];
    }

    /**
     * Cancel a Duffel order on the airline/Duffel side only.
     * Returns the SAR amount Duffel will refund (0 if non-refundable).
     * Does NOT modify the local database — the caller is responsible for all DB changes.
     */
    public function cancelForRefund(Order $order): float
    {
        $duffelOrderId = $order->metadata['duffel_order_id'] ?? null;
        if (!$duffelOrderId) {
            throw new \InvalidArgumentException('هذا الطلب لم يتم حجزه عبر Duffel');
        }

        if ($order->status->name === 'cancelled') {
            throw new \InvalidArgumentException('تم إلغاء هذا الطلب مسبقاً');
        }

        $cancellation = $this->duffel->cancelOrder($duffelOrderId);

        return isset($cancellation['refund_amount'], $cancellation['refund_currency'])
            ? $this->toSar((float) $cancellation['refund_amount'], strtoupper($cancellation['refund_currency']))
            : 0.0;
    }

    // --- Private helpers ---

    private function persistBooking(
        array $duffelOrder,
        int $agencyId,
        int $userId,
        array $data,
        Wallet $wallet,
        float $sarAmount,
        string $duffelAmount,
        string $duffelCurrency,
        string $idempotencyKey,
    ): Order {
        $confirmedStatus      = OrderStatus::where('name', 'confirmed')->firstOrFail();
        $orderNumber          = ReferenceNumberGenerator::make('ORD');
        $firstSlice           = $duffelOrder['slices'][0] ?? [];
        $commission           = (float) ($this->pointSettingService->current()->agency_commission_amount ?? 0);
        $totalDebit           = round($sarAmount + $commission, 3);

        return DB::transaction(function () use (
            $duffelOrder, $agencyId, $confirmedStatus, $orderNumber,
            $firstSlice, $data, $userId, $wallet, $sarAmount, $duffelAmount, $duffelCurrency,
            $idempotencyKey, $commission, $totalDebit
        ) {
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            if (!$wallet->canDebit($totalDebit)) {
                throw new InsufficientWalletBalanceException();
            }

            $this->debitWallet($wallet, $totalDebit, $duffelAmount, $duffelCurrency, $userId);

            $order = Order::create([
                'order_number'      => $orderNumber,
                'agency_id'         => $agencyId,
                'status_id'         => $confirmedStatus->id,
                'estimated_price'   => $duffelOrder['total_amount'],
                'total_passengers'  => count($data['passengers']),
                'departure_date'    => $this->extractDate($firstSlice['segments'][0]['departing_at'] ?? null),
                'return_date'       => isset($duffelOrder['slices'][1])
                    ? $this->extractDate($duffelOrder['slices'][1]['segments'][0]['departing_at'] ?? null)
                    : null,
                'trip_type'         => count($duffelOrder['slices']) > 1 ? 'roundtrip' : 'oneway',
                'itinerary_details' => $duffelOrder,
                'metadata'          => [
                    'source'                => 'duffel',
                    'duffel_order_id'       => $duffelOrder['id'],
                    'booking_reference'     => $duffelOrder['booking_reference'] ?? null,
                    'paid_sar'              => $totalDebit,
                    'paid_money'            => $this->pointSettingService->pointsToMoney($totalDebit),
                    'duffel_amount'         => $duffelAmount,
                    'duffel_currency'       => $duffelCurrency,
                    'idempotency_key'       => $idempotencyKey,
                    'agency_commission'     => $commission,
                    'duffel_cost_sar'       => $sarAmount,
                ],
                'created_by' => $userId,
            ]);

            $cabinClass       = $this->referenceResolver->mapCabinClass('economy');
            $passengerTypeMap = PassengerType::whereIn('code', ['ADT', 'CHD', 'INF'])->pluck('id', 'code')->all();
            $duffelTypeToCode = ['adult' => 'ADT', 'child' => 'CHD', 'infant' => 'INF'];
            $sequence         = 1;

            foreach ($duffelOrder['slices'] as $sliceIndex => $slice) {
                $leg = $sliceIndex === 0 ? 'outbound' : 'inbound';
                foreach ($slice['segments'] as $segment) {
                    $airlineCode = $segment['marketing_carrier']['iata_code'] ?? null;
                    $airlineName = $segment['marketing_carrier']['name'] ?? null;
                    $airline     = $airlineCode
                        ? $this->referenceResolver->findOrCreateAirline($airlineCode, $airlineName)
                        : $this->referenceResolver->findOrCreateAirlineByName($airlineName ?? 'Unknown');

                    $order->segments()->create([
                        'airline_id'           => $airline->id,
                        'departure_airport_id' => $this->referenceResolver->findOrCreateAirport($segment['origin']['iata_code'])->id,
                        'arrival_airport_id'   => $this->referenceResolver->findOrCreateAirport($segment['destination']['iata_code'])->id,
                        'cabin_class_id'       => $cabinClass->id,
                        'departure_datetime'   => $segment['departing_at'],
                        'arrival_datetime'     => $segment['arriving_at'],
                        'flight_number'        => ($airlineCode ?? '') . ($segment['marketing_carrier_flight_number'] ?? ''),
                        'aircraft_type'        => $segment['aircraft']['name'] ?? null,
                        'duration_minutes'     => $this->parseDuration($segment['duration'] ?? ''),
                        'sequence'             => $sequence++,
                        'metadata'             => ['leg' => $leg],
                    ]);
                }
            }

            foreach ($data['passengers'] as $index => $passenger) {
                $duffelType = $passenger['duffel_type'] ?? 'adult';
                $typeCode   = $duffelTypeToCode[$duffelType] ?? 'ADT';
                $order->passengers()->create([
                    'passenger_type_id' => $passengerTypeMap[$typeCode] ?? $passengerTypeMap['ADT'],
                    'title'             => $passenger['title'] ?? null,
                    'first_name'        => $passenger['given_name'],
                    'last_name'         => $passenger['family_name'],
                    'sequence'          => $index + 1,
                    'metadata'          => [
                        'duffel_passenger_id' => $passenger['duffel_passenger_id'] ?? null,
                        'email'               => $passenger['email'] ?? null,
                    ],
                ]);
            }

            OrderStatusHistory::create([
                'order_id'       => $order->id,
                'from_status_id' => null,
                'to_status_id'   => $confirmedStatus->id,
                'reason'         => sprintf(
                    'تم الحجز المباشر - رقم الحجز: %s | تم خصم %.2f نقطة (تكلفة: %.2f + عمولة: %.2f)',
                    $duffelOrder['booking_reference'] ?? '',
                    $totalDebit,
                    $sarAmount,
                    $commission
                ),
                'created_by'     => $userId,
                'created_at'     => now(),
            ]);

            return $order;
        });
    }

    private function debitWallet(Wallet $wallet, float $sarAmount, mixed $originalAmount, string $originalCurrency, int $userId): void
    {
        $type        = WalletTransactionType::firstOrCreate(['name' => 'duffel_booking'], ['label' => 'حجز Duffel']);
        $newBalance  = (float) $wallet->balance - $sarAmount;

        $wallet->transactions()->create([
            'uuid'                => Str::uuid(),
            'transaction_type_id' => $type->id,
            'amount'              => -$sarAmount,
            'money_equivalent'    => $sarAmount,
            'balance_after'       => $newBalance,
            'description'         => sprintf('خصم حجز مباشر: %s %s = %.2f نقطة', $originalAmount, $originalCurrency, $sarAmount),
            'created_by'          => $userId,
            'created_at'          => now(),
        ]);

        $wallet->update(['balance' => $newBalance]);
    }

    private function creditWallet(Wallet $wallet, float $sarAmount, mixed $originalAmount, string $originalCurrency, int $userId, string $orderNumber): void
    {
        $type       = WalletTransactionType::firstOrCreate(['name' => 'duffel_refund'], ['label' => 'استرداد Duffel']);
        $newBalance = (float) $wallet->balance + $sarAmount;

        $wallet->transactions()->create([
            'uuid'                => Str::uuid(),
            'transaction_type_id' => $type->id,
            'amount'              => $sarAmount,
            'money_equivalent'    => $sarAmount,
            'balance_after'       => $newBalance,
            'description'         => sprintf('استرداد إلغاء حجز مباشر #%s: %s %s = %.2f نقطة', $orderNumber, $originalAmount, $originalCurrency, $sarAmount),
            'created_by'          => $userId,
            'created_at'          => now(),
        ]);

        $wallet->update(['balance' => $newBalance]);
    }

    private function filterRealDocuments(array $documents): array
    {
        return array_values(array_filter($documents, function ($doc) {
            return !empty($doc['unique_identifier'] ?? null);
        }));
    }

    private function toSar(float $amount, string $currency): float
    {
        return $this->pointSettingService->currencyToSar($amount, $currency);
    }

    private function extractDate(?string $datetime): ?string
    {
        return $datetime ? substr($datetime, 0, 10) : null;
    }

    private function parseDuration(string $iso8601): int
    {
        if (!$iso8601) {
            return 0;
        }
        preg_match('/P(?:(\d+)D)?T(?:(\d+)H)?(?:(\d+)M)?/', $iso8601, $m);
        return ((int) ($m[1] ?? 0) * 24 + (int) ($m[2] ?? 0)) * 60 + (int) ($m[3] ?? 0);
    }
}
