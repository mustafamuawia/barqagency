<?php

use App\Exceptions\DuffelBookingUncertainException;
use App\Exceptions\DuffelOfferExpiredException;
use App\Models\DuffelBookingAttempt;
use App\Models\AgencyType;
use App\Models\AgencyUser;
use App\Models\Order;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FlightSearch\DuffelClient;
use App\Services\FlightSearch\DuffelOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// Shared fixtures
// ---------------------------------------------------------------------------

function duffelSecurityFixtures(): array
{
    (new \Database\Seeders\ReferenceDataSeeder())->run();

    $adminType   = UserType::where('name', 'admin')->firstOrFail();
    $agencyType  = UserType::where('name', 'b2b')->firstOrFail();

    $admin      = User::factory()->create(['user_type_id' => $adminType->id]);
    $agencyUser = User::factory()->create(['user_type_id' => $agencyType->id]);

    $agency = TravelAgency::create([
        'name'           => 'Security Test Agency',
        'email'          => 'sec-agency-' . uniqid() . '@example.com',
        'agency_type_id' => AgencyType::first()->id,
        'status'         => 'active',
        'credit_limit'   => 0,
    ]);

    AgencyUser::create(['agency_id' => $agency->id, 'user_id' => $agencyUser->id, 'role' => 'owner']);

    // Wallet with enough SAR balance for the real cached price (424.67) but not more
    Wallet::create([
        'agency_id'    => $agency->id,
        'balance'      => 500.00,
        'credit_limit' => 0,
        'created_by'   => $admin->id,
    ]);

    return compact('admin', 'agencyUser', 'agency');
}

/**
 * Returns a minimal Duffel order response that satisfies DuffelOrderService::book().
 * Uses airports/airlines that are already seeded by ReferenceDataSeeder.
 */
function fakeDuffelOrder(string $orderId = 'ord_test001', string $pnr = 'TST001'): array
{
    return [
        'id'               => $orderId,
        'booking_reference' => $pnr,
        'total_amount'     => '113.245',
        'total_currency'   => 'USD',
        'slices' => [
            [
                'segments' => [
                    [
                        'departing_at'                   => '2026-11-20T08:00:00',
                        'arriving_at'                    => '2026-11-20T11:00:00',
                        'duration'                       => 'PT3H',
                        'marketing_carrier'              => ['iata_code' => 'EK', 'name' => 'Emirates'],
                        'marketing_carrier_flight_number' => '123',
                        'operating_carrier'              => ['iata_code' => 'EK', 'name' => 'Emirates'],
                        'origin'      => ['iata_code' => 'CAI'],
                        'destination' => ['iata_code' => 'DXB'],
                        'aircraft'    => ['name' => 'Boeing 737'],
                    ],
                ],
            ],
        ],
        'passengers' => [
            [
                'id'          => 'pas_test001',
                'given_name'  => 'Ahmed',
                'family_name' => 'Ali',
                'type'        => 'adult',
                'gender'      => 'm',
            ],
        ],
        'documents' => [
            [
                'unique_identifier' => '1234567890123',
                'passenger_ids'     => ['pas_test001'],
                'type'              => 'electronic_ticket',
            ],
        ],
    ];
}

function fakeDuffelHttp(): void
{
    $order = fakeDuffelOrder();
    Http::fake([
        '*/air/orders'   => Http::response(['data' => $order], 201),
        '*/air/orders/*' => Http::response(['data' => $order], 200),
    ]);
}

/** Minimum valid booking payload — no pricing fields. */
function validBookingPayload(string $offerId = 'off_test001'): array
{
    return [
        'offer_id'   => $offerId,
        'passengers' => [
            [
                'duffel_passenger_id' => 'pas_test001',
                'duffel_type'         => 'adult',
                'title'               => 'mr',
                'given_name'          => 'Ahmed',
                'family_name'         => 'Ali',
                'born_on'             => '1990-01-01',
                'email'               => 'ahmed@test.com',
                'phone_number'        => '+966500000000',
                'gender'              => 'm',
            ],
        ],
    ];
}

function seedOfferCache(
    string $offerId      = 'off_test001',
    string $amount       = '113.245',
    string $currency     = 'USD',
    float  $sar          = 424.67,
    array  $passengerIds = ['pas_test001']
): void {
    Cache::put('duffel_offer:' . $offerId, [
        'amount'        => $amount,
        'currency'      => $currency,
        'sar'           => $sar,
        'passenger_ids' => $passengerIds,
    ], now()->addMinutes(15));
}

// ---------------------------------------------------------------------------
// C-3: itinerary_details is persisted
// ---------------------------------------------------------------------------

test('C-3: itinerary_details is saved to the orders table after a successful booking', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    $order = Order::first();
    expect($order)->not->toBeNull()
        ->and($order->itinerary_details)->not->toBeNull()
        ->and($order->itinerary_details['id'])->toBe('ord_test001')
        ->and($order->itinerary_details['booking_reference'])->toBe('TST001');
});

// ---------------------------------------------------------------------------
// C-1: SAR amount is ALWAYS taken from the server cache, never the client
// ---------------------------------------------------------------------------

test('C-1: wallet is debited by the server-cached SAR amount, not the client-supplied value', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    // Cache says the real price is 424.67 SAR
    seedOfferCache(sar: 424.67);

    $payload = validBookingPayload();
    // Client attempts to pay only 0.01 SAR — this field is not even validated any more
    // but we include it to prove it has absolutely no effect.
    $payload['total_price_sar'] = 0.01;

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', $payload)
        ->assertStatus(201);

    $tx = WalletTransaction::first();
    expect($tx)->not->toBeNull()
        // Deduction must equal the cached price, not the tampered value.
        // Compare as rounded floats to avoid decimal:3 storage precision noise.
        ->and(round((float) $tx->amount, 2))->toBe(-424.67)
        ->and(round((float) $tx->balance_after, 2))->toBe(round(500.00 - 424.67, 2));
});

test('C-1: wallet balance after booking equals initial balance minus the cached SAR price', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache(sar: 300.00);

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    $wallet = Wallet::first();
    expect((float) $wallet->balance)->toBe(200.00); // 500 - 300
});

// ---------------------------------------------------------------------------
// C-2: Duffel payment amount is ALWAYS taken from the server cache
// ---------------------------------------------------------------------------

test('C-2: Duffel receives the server-cached amount, not any client-supplied amount', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    // Cache holds the real Duffel price
    seedOfferCache(amount: '113.245', currency: 'USD');

    $payload = validBookingPayload();
    // Client tries to pay Duffel only $0.01
    $payload['duffel_total_amount']   = '0.01';
    $payload['duffel_total_currency'] = 'USD';

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', $payload)
        ->assertStatus(201);

    // Assert the outgoing Duffel request contained the correct cached amount.
    // The body is JSON — decode it to access nested keys.
    Http::assertSent(function ($request) {
        if (!str_contains($request->url(), '/air/orders')) {
            return false;
        }
        $body    = json_decode($request->body(), true);
        $payment = $body['data']['payments'][0] ?? [];
        return ($payment['amount'] ?? null) === '113.245'
            && ($payment['currency'] ?? null) === 'USD';
    });
});

test('C-2: booking fails with 409 expired when the offer is not in the server cache', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    // No cache entry — simulates an expired offer

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(409)
        ->assertJsonFragment(['expired' => true]);

    // No order should have been created and no wallet deduction should have occurred
    expect(Order::count())->toBe(0)
        ->and(WalletTransaction::count())->toBe(0);
});

test('C-2: booking fails with 409 expired when offer_id in cache does not match', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    // Cache is for a different offer
    seedOfferCache(offerId: 'off_different');

    $payload              = validBookingPayload();
    $payload['offer_id']  = 'off_test001'; // not in cache

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', $payload)
        ->assertStatus(409)
        ->assertJsonFragment(['expired' => true]);
});

test('C-2: booking fails when passenger ID is not in the cached offer', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    // Cache has only pas_test001; client tries to inject pas_from_expensive_offer
    seedOfferCache(passengerIds: ['pas_test001']);

    $payload = validBookingPayload();
    $payload['passengers'][0]['duffel_passenger_id'] = 'pas_from_expensive_offer';

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', $payload)
        ->assertStatus(422); // \InvalidArgumentException → caught in controller → 422
});

// ---------------------------------------------------------------------------
// C-2: Offer cache is invalidated after a successful booking
// ---------------------------------------------------------------------------

test('C-2: offer cache is cleared after a successful booking preventing re-use', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    // First booking succeeds
    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    // Second booking with the same offer_id — cache is gone
    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(409)
        ->assertJsonFragment(['expired' => true]);

    // Only one order and one wallet deduction should exist
    expect(Order::count())->toBe(1)
        ->and(WalletTransaction::count())->toBe(1);
});

// ---------------------------------------------------------------------------
// C-1: Insufficient balance uses server-cached SAR, not client value
// ---------------------------------------------------------------------------

test('C-1: booking is rejected when cached SAR price exceeds wallet balance', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    // Cache says real price is 600 SAR but wallet has only 500
    seedOfferCache(sar: 600.00);

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(422);

    expect(Order::count())->toBe(0)
        ->and(WalletTransaction::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Validation: pricing fields are stripped from the validated request
// ---------------------------------------------------------------------------

test('booking request is accepted without total_price_sar, duffel_total_amount, duffel_total_currency', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    // Send payload with absolutely none of the old pricing fields
    $payload = validBookingPayload();
    unset($payload['total_price_sar'], $payload['duffel_total_amount'], $payload['duffel_total_currency']);

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', $payload)
        ->assertStatus(201);
});

// ---------------------------------------------------------------------------
// C-3: itinerary_details round-trip — JSON cast works correctly
// ---------------------------------------------------------------------------

test('C-3: itinerary_details can be read back as an array from the Order model', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    $order = Order::first();
    // Eloquent must deserialise the JSON column as an array (not a string)
    expect($order->itinerary_details)->toBeArray()
        ->and($order->itinerary_details['slices'])->toBeArray()
        ->and($order->itinerary_details['passengers'][0]['id'])->toBe('pas_test001');
});

// ---------------------------------------------------------------------------
// H-1: Idempotency key is generated and stored
// ---------------------------------------------------------------------------

test('H-1: idempotency key UUID is stored in order metadata after a successful booking', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    $order = Order::first();
    expect($order)->not->toBeNull()
        ->and($order->metadata)->toHaveKey('idempotency_key')
        ->and($order->metadata['idempotency_key'])->toMatch(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/'
        );
});

test('H-1: Idempotency-Key header is sent to Duffel on the booking request', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/air/orders')
            && !str_contains($request->url(), '/air/orders/')
            && $request->hasHeader('Idempotency-Key')
            && strlen($request->header('Idempotency-Key')[0] ?? '') === 36;
    });
});

test('H-1: booking returns 202 uncertain when DuffelClient reports an uncertain booking status', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    seedOfferCache();

    // Simulate the exception that DuffelClient::bookOrder() throws when a ConnectionException
    // occurs — the production code converts a raw ConnectionException into this typed exception.
    $mock = Mockery::mock(DuffelClient::class);
    $mock->shouldReceive('bookOrder')->andThrow(new DuffelBookingUncertainException());
    app()->instance(DuffelClient::class, $mock);

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(202)
        ->assertJsonFragment(['status' => 'uncertain']);

    // Wallet must NOT have been debited and no order created.
    expect(Order::count())->toBe(0)
        ->and(WalletTransaction::count())->toBe(0);
});

// ---------------------------------------------------------------------------
// H-2: Atomic duplicate-booking protection
// ---------------------------------------------------------------------------

test('H-2: concurrent booking with same offer_id returns 409 with duplicate flag', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    // Manually hold the cache lock to simulate a concurrent in-flight request.
    $held = Cache::lock('duffel_booking_lock:off_test001', 90);
    expect($held->get())->toBeTrue();

    try {
        $this->actingAs($agencyUser)
            ->postJson('/api/v1/duffel/book', validBookingPayload())
            ->assertStatus(409)
            ->assertJsonFragment(['duplicate' => true]);

        expect(Order::count())->toBe(0)
            ->and(WalletTransaction::count())->toBe(0);
    } finally {
        $held->release();
    }
});

test('H-2: lock is released after a successful booking so a subsequent booking can proceed normally', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    seedOfferCache(offerId: 'off_test001', sar: 200.00);
    fakeDuffelHttp();

    // First booking succeeds and must release the lock.
    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload('off_test001'))
        ->assertStatus(201);

    // A fresh lock on the same key must now be acquirable (proves the lock was released).
    $lock = Cache::lock('duffel_booking_lock:off_test001', 1);
    expect($lock->get())->toBeTrue();
    $lock->release();
});

// ---------------------------------------------------------------------------
// Persistent tracking: DuffelBookingAttempt is created for every booking
// ---------------------------------------------------------------------------

test('Tracking: a booking attempt record is created for every Duffel booking', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(201);

    $attempt = DuffelBookingAttempt::first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->offer_id)->toBe('off_test001')
        ->and($attempt->idempotency_key)->toBe(Order::first()->metadata['idempotency_key'])
        ->and($attempt->status)->toBe('reconciled')
        ->and($attempt->order_id)->toBe(Order::first()->id);
});

test('Tracking: booking attempt stays pending and 202 response includes booking_attempt_id on connection failure', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    seedOfferCache();

    $mock = Mockery::mock(DuffelClient::class);
    $mock->shouldReceive('bookOrder')->andThrow(new DuffelBookingUncertainException());
    app()->instance(DuffelClient::class, $mock);

    $response = $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(202)
        ->assertJsonStructure(['status', 'message', 'booking_attempt_id', 'retry_after_seconds']);

    $uuid = $response->json('booking_attempt_id');
    expect($uuid)->not->toBeEmpty();

    $attempt = DuffelBookingAttempt::where('uuid', $uuid)->first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('pending')
        ->and($attempt->order_id)->toBeNull();

    // Wallet untouched, no order created.
    expect(Order::count())->toBe(0)
        ->and(WalletTransaction::count())->toBe(0);
});

test('Tracking: booking attempt is marked failed when Duffel returns a known error', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    seedOfferCache();

    $mock = Mockery::mock(DuffelClient::class);
    $mock->shouldReceive('bookOrder')
        ->andThrow(new \App\Exceptions\DuffelOfferExpiredException());
    app()->instance(DuffelClient::class, $mock);

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(409)
        ->assertJsonFragment(['expired' => true]);

    $attempt = DuffelBookingAttempt::first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->status)->toBe('failed');
});

// ---------------------------------------------------------------------------
// Reconciliation: reconcileAttempt() correctly finalises a pending attempt
// ---------------------------------------------------------------------------

test('Reconciliation: reconcileAttempt() debits wallet and creates an order for a pending attempt', function () {
    ['agencyUser' => $agencyUser, 'agency' => $agency] = duffelSecurityFixtures();
    fakeDuffelHttp();
    seedOfferCache();

    // Create a pending attempt as if the connection failed during the original booking.
    $attempt = DuffelBookingAttempt::create([
        'agency_id'         => $agency->id,
        'created_by'        => $agencyUser->id,
        'offer_id'          => 'off_test001',
        'idempotency_key'   => (string) \Illuminate\Support\Str::uuid(),
        'duffel_passengers' => [[
            'id'           => 'pas_test001',
            'title'        => 'mr',
            'given_name'   => 'Ahmed',
            'family_name'  => 'Ali',
            'born_on'      => '1990-01-01',
            'email'        => 'ahmed@test.com',
            'phone_number' => '+966500000000',
            'gender'       => 'm',
            'duffel_type'  => 'adult',
        ]],
        'duffel_amount'     => '113.245',
        'duffel_currency'   => 'USD',
        'sar_amount'        => 424.67,
        'status'            => 'pending',
        'last_attempted_at' => now()->subMinutes(10),
    ]);

    $service = app(\App\Services\FlightSearch\DuffelOrderService::class);
    $result  = $service->reconcileAttempt($attempt->fresh());

    expect($result)->toBe('reconciled')
        ->and(Order::count())->toBe(1)
        ->and(WalletTransaction::count())->toBe(1);

    $attempt->refresh();
    expect($attempt->status)->toBe('reconciled')
        ->and($attempt->order_id)->not->toBeNull()
        ->and($attempt->resolved_at)->not->toBeNull();
});

test('Reconciliation: reconcileAttempt() marks attempt failed when idempotency window has expired', function () {
    ['agency' => $agency, 'agencyUser' => $agencyUser] = duffelSecurityFixtures();

    $attempt = DuffelBookingAttempt::create([
        'agency_id'         => $agency->id,
        'created_by'        => $agencyUser->id,
        'offer_id'          => 'off_test001',
        'idempotency_key'   => (string) \Illuminate\Support\Str::uuid(),
        'duffel_passengers' => [],
        'duffel_amount'     => '113.245',
        'duffel_currency'   => 'USD',
        'sar_amount'        => 424.67,
        'status'            => 'pending',
    ]);

    // Eloquent ignores created_at in mass-assignment; use a raw update to backdate it.
    \Illuminate\Support\Facades\DB::table('duffel_booking_attempts')
        ->where('id', $attempt->id)
        ->update(['created_at' => now()->subHours(25)]);

    $service = app(\App\Services\FlightSearch\DuffelOrderService::class);
    $result  = $service->reconcileAttempt($attempt->fresh());

    expect($result)->toBe('expired');
    expect($attempt->fresh()->status)->toBe('failed');
});

// ---------------------------------------------------------------------------
// Status endpoint: booking attempt polling
// ---------------------------------------------------------------------------

test('Status endpoint: agency can poll their booking attempt status', function () {
    ['agencyUser' => $agencyUser, 'agency' => $agency] = duffelSecurityFixtures();

    $attempt = DuffelBookingAttempt::create([
        'agency_id'         => $agency->id,
        'created_by'        => $agencyUser->id,
        'offer_id'          => 'off_test001',
        'idempotency_key'   => (string) \Illuminate\Support\Str::uuid(),
        'duffel_passengers' => [],
        'duffel_amount'     => '0',
        'duffel_currency'   => 'USD',
        'sar_amount'        => 0,
        'status'            => 'pending',
    ]);

    $this->actingAs($agencyUser)
        ->getJson('/api/v1/duffel/booking-attempts/' . $attempt->uuid)
        ->assertStatus(200)
        ->assertJsonFragment([
            'uuid'   => $attempt->uuid,
            'status' => 'pending',
        ]);
});

test('Status endpoint: agency cannot view another agency booking attempt', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();

    // Create a second agency and its attempt.
    (new \Database\Seeders\ReferenceDataSeeder())->run();
    $otherUser   = \App\Models\User::factory()->create(['user_type_id' => \App\Models\UserType::where('name', 'b2b')->first()->id]);
    $otherAgency = \App\Models\TravelAgency::create([
        'name' => 'Other Agency', 'email' => 'other@example.com',
        'agency_type_id' => \App\Models\AgencyType::first()->id, 'status' => 'active', 'credit_limit' => 0,
    ]);
    \App\Models\AgencyUser::create(['agency_id' => $otherAgency->id, 'user_id' => $otherUser->id, 'role' => 'owner']);
    \App\Models\Wallet::create(['agency_id' => $otherAgency->id, 'balance' => 0, 'credit_limit' => 0, 'created_by' => $otherUser->id]);

    $attempt = DuffelBookingAttempt::create([
        'agency_id'         => $otherAgency->id,
        'created_by'        => $otherUser->id,
        'offer_id'          => 'off_other',
        'idempotency_key'   => (string) \Illuminate\Support\Str::uuid(),
        'duffel_passengers' => [],
        'duffel_amount'     => '0',
        'duffel_currency'   => 'USD',
        'sar_amount'        => 0,
        'status'            => 'pending',
    ]);

    // The first agency user should get a 404.
    $this->actingAs($agencyUser)
        ->getJson('/api/v1/duffel/booking-attempts/' . $attempt->uuid)
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// H-2: Lock is released even when an exception occurs mid-booking
// ---------------------------------------------------------------------------

test('H-2: lock is released after a known Duffel failure, allowing the next request to proceed', function () {
    ['agencyUser' => $agencyUser] = duffelSecurityFixtures();
    seedOfferCache(sar: 424.67);

    // First request: Duffel rejects the offer (definitive failure)
    $mock = Mockery::mock(DuffelClient::class);
    $mock->shouldReceive('bookOrder')->andThrow(new \App\Exceptions\DuffelOfferExpiredException());
    app()->instance(DuffelClient::class, $mock);

    $this->actingAs($agencyUser)
        ->postJson('/api/v1/duffel/book', validBookingPayload())
        ->assertStatus(409);

    // The lock must be released — a new lock on the same key must be acquirable.
    $lock = Cache::lock('duffel_booking_lock:off_test001', 1);
    expect($lock->get())->toBeTrue();
    $lock->release();
});
