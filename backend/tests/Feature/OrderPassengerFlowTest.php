<?php

use App\DTOs\Order\CreateOrderData;
use App\Models\AgencyType;
use App\Models\AgencyUser;
use App\Models\CabinClass;
use App\Models\PassengerType;
use App\Models\PointSetting;
use App\Models\QuoteStatus;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Order\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * The agency requests an order with nothing but a head count per passenger type — that mix
 * is what the admin prices against — and fills in the traveller details later, when it
 * approves the quote. These tests pin both ends of that split.
 */
function seedPassengerFlowFixtures(): array
{
    (new \Database\Seeders\ReferenceDataSeeder())->run();
    PointSetting::create(['money_amount' => 1, 'point_amount' => 1]);

    $adminType = UserType::where('name', 'admin')->firstOrFail();
    $agencyUserType = UserType::where('name', 'b2b')->firstOrFail();

    $admin = User::factory()->create(['user_type_id' => $adminType->id]);
    $agencyUser = User::factory()->create(['user_type_id' => $agencyUserType->id]);

    $agency = TravelAgency::create([
        'name' => 'Passenger Flow Agency',
        'email' => 'agency-'.uniqid().'@example.com',
        'agency_type_id' => AgencyType::first()->id,
        'status' => 'active',
        'credit_limit' => 0,
    ]);

    AgencyUser::create(['agency_id' => $agency->id, 'user_id' => $agencyUser->id, 'role' => 'owner']);
    Wallet::create(['agency_id' => $agency->id, 'balance' => 0, 'credit_limit' => 5000, 'created_by' => $admin->id]);

    return compact('admin', 'agencyUser', 'agency');
}

function createOrderWithCounts(User $agencyUser, array $counts)
{
    test()->actingAs($agencyUser);

    return app(OrderService::class)->create($agencyUser, new CreateOrderData(
        agencyId: null,
        tripType: 'one_way',
        departureDate: now()->addDays(14)->toDateString(),
        returnDate: null,
        segments: [[
            'departure_airport_name' => 'Khartoum',
            'arrival_airport_name' => 'Dubai',
            'cabin_class_id' => CabinClass::where('code', 'Y')->firstOrFail()->id,
            'sequence' => 1,
        ]],
        passengerCounts: $counts,
    ));
}

function passengerDetails(int $id, string $firstName): array
{
    return [
        'id' => $id,
        'title' => 'السيد',
        'first_name' => $firstName,
        'last_name' => 'Ahmed',
        'date_of_birth' => '1990-05-01',
        'nationality' => 'Sudanese',
        'passport_number' => 'P'.$id.'123456',
        'passport_expiry' => now()->addYears(3)->toDateString(),
    ];
}

test('an order is created with one unnamed passenger row per head, typed as requested', function () {
    ['agencyUser' => $agencyUser] = seedPassengerFlowFixtures();

    $adult = PassengerType::where('code', 'ADT')->firstOrFail();
    $child = PassengerType::where('code', 'CHD')->firstOrFail();
    $infant = PassengerType::where('code', 'INF')->firstOrFail();

    $order = createOrderWithCounts($agencyUser, [
        ['passenger_type_id' => $adult->id, 'count' => 2],
        ['passenger_type_id' => $child->id, 'count' => 1],
        // A type the agency left at zero carries nobody and must not create a seat.
        ['passenger_type_id' => $infant->id, 'count' => 0],
    ]);

    expect($order->total_passengers)->toBe(3)
        ->and($order->passengers)->toHaveCount(3)
        ->and($order->passengers->pluck('passenger_type_id')->all())->toBe([$adult->id, $adult->id, $child->id])
        ->and($order->passengers->pluck('sequence')->all())->toBe([1, 2, 3])
        ->and($order->passengers->every(fn ($p) => $p->first_name === null))->toBeTrue();
});

test('approval refuses to go through while a passenger has no details', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser] = seedPassengerFlowFixtures();

    $adult = PassengerType::where('code', 'ADT')->firstOrFail();
    $order = createOrderWithCounts($agencyUser, [['passenger_type_id' => $adult->id, 'count' => 2]]);
    quoteOrder($order, $admin);

    $this->actingAs($agencyUser);

    // Only the first of the two passengers is filled in.
    expect(fn () => app(OrderService::class)->approve($agencyUser, $order->fresh(['status']), [
        passengerDetails($order->passengers[0]->id, 'Ali'),
    ]))->toThrow(ValidationException::class);

    expect($order->fresh(['status'])->status->name)->toBe('pricing_sent');
    expect(\App\Models\WalletTransaction::count())->toBe(0);
});

test('approval saves the traveller details the agency entered and debits the wallet', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser] = seedPassengerFlowFixtures();

    $adult = PassengerType::where('code', 'ADT')->firstOrFail();
    $order = createOrderWithCounts($agencyUser, [['passenger_type_id' => $adult->id, 'count' => 2]]);
    quoteOrder($order, $admin);

    $this->actingAs($agencyUser);
    $approved = app(OrderService::class)->approve($agencyUser, $order->fresh(['status']), [
        passengerDetails($order->passengers[0]->id, 'Ali'),
        passengerDetails($order->passengers[1]->id, 'Sara'),
    ]);

    expect($approved->status->name)->toBe('approved');

    $passengers = $order->fresh(['passengers'])->passengers;
    expect($passengers->pluck('first_name')->all())->toBe(['Ali', 'Sara'])
        ->and($passengers->pluck('passport_number')->all())->toBe(['P'.$passengers[0]->id.'123456', 'P'.$passengers[1]->id.'123456'])
        // The type the admin priced against is never overwritten by the details payload.
        ->and($passengers->pluck('passenger_type_id')->unique()->all())->toBe([$adult->id]);
});

test('approval rejects details for a passenger that belongs to another order', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser] = seedPassengerFlowFixtures();

    $adult = PassengerType::where('code', 'ADT')->firstOrFail();
    $order = createOrderWithCounts($agencyUser, [['passenger_type_id' => $adult->id, 'count' => 1]]);
    $other = createOrderWithCounts($agencyUser, [['passenger_type_id' => $adult->id, 'count' => 1]]);
    quoteOrder($order, $admin);

    $this->actingAs($agencyUser);

    expect(fn () => app(OrderService::class)->approve($agencyUser, $order->fresh(['status']), [
        passengerDetails($other->passengers[0]->id, 'Ali'),
    ]))->toThrow(ValidationException::class);
});

function quoteOrder($order, User $admin): void
{
    $order->quotes()->create([
        'status_id' => QuoteStatus::where('name', 'sent')->firstOrFail()->id,
        'base_price' => 500,
        'taxes' => 0,
        'fees' => 0,
        'total_price' => 500,
        'currency_code' => 'SDG',
        'created_by' => $admin->id,
    ]);

    $order->transitionTo('pricing_sent');
}
