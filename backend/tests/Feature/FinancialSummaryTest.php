<?php

use App\DTOs\Refund\CreateRefundData;
use App\DTOs\Refund\DecideRefundData;
use App\DTOs\Report\ReportFilterData;
use App\Models\AgencyType;
use App\Models\AgencyUser;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\PointSetting;
use App\Models\QuoteStatus;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Admin\DashboardService;
use App\Services\Order\OrderService;
use App\Services\Refund\RefundService;
use App\Services\Report\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Regression coverage for the wallet_transactions "amount vs money_equivalent" bug:
 * DashboardService::getStats() and ReportService::summary() must sum money_equivalent
 * (real money) rather than amount (points) for the "this month" financial summary and
 * the reports page. Because the points/money exchange rate is admin-configurable and not
 * always 1:1, summing the wrong column silently produces the wrong numbers whenever the
 * rate diverges from 1:1 — which is exactly the scenario this test sets up.
 */
function seedFinancialSummaryFixtures(): array
{
    (new \Database\Seeders\ReferenceDataSeeder())->run();

    // Non-1:1 rate: 1 money = 2 points. At 1:1 the amount/money_equivalent columns would
    // coincide by coincidence and this test would not actually distinguish the bug fix.
    PointSetting::create(['money_amount' => 1, 'point_amount' => 2]);

    $adminType = UserType::where('name', 'admin')->firstOrFail();
    $agencyUserType = UserType::where('name', 'b2b')->firstOrFail();
    $agencyType = AgencyType::first();

    $admin = User::factory()->create(['user_type_id' => $adminType->id]);
    $agencyUser = User::factory()->create(['user_type_id' => $agencyUserType->id]);

    $agency = TravelAgency::create([
        'name' => 'Test Travel Agency',
        'email' => 'agency-'.uniqid().'@example.com',
        'agency_type_id' => $agencyType->id,
        'status' => 'active',
        'credit_limit' => 0,
    ]);

    AgencyUser::create([
        'agency_id' => $agency->id,
        'user_id' => $agencyUser->id,
        'role' => 'owner',
    ]);

    $wallet = Wallet::create([
        'agency_id' => $agency->id,
        'balance' => 0,
        'credit_limit' => 5000,
        'created_by' => $admin->id,
    ]);

    $pricingSentStatus = OrderStatus::where('name', 'pricing_sent')->firstOrFail();
    $order = Order::create([
        'order_number' => 'ORD-TEST-'.uniqid(),
        'agency_id' => $agency->id,
        'status_id' => $pricingSentStatus->id,
        'total_passengers' => 1,
        'departure_date' => now()->addDays(10)->toDateString(),
        'created_by' => $admin->id,
    ]);

    $sentQuoteStatus = QuoteStatus::where('name', 'sent')->firstOrFail();
    $order->quotes()->create([
        'status_id' => $sentQuoteStatus->id,
        'base_price' => 500,
        'taxes' => 0,
        'fees' => 0,
        'total_price' => 500,
        'currency_code' => 'SDG',
        'created_by' => $admin->id,
    ]);

    return compact('admin', 'agencyUser', 'agency', 'wallet', 'order');
}

test('order approval and refund approval record points and money on separate columns under a non-1:1 rate', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser, 'order' => $order] = seedFinancialSummaryFixtures();

    // Booking debit: agency approves the priced order (real flow, no mocking).
    $this->actingAs($agencyUser);
    $order = app(OrderService::class)->approve($agencyUser, $order->fresh());

    expect($order->status->name)->toBe('approved');

    $bookingTx = WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'booking_debit'))->firstOrFail();
    // 500 money * (2 points / 1 money) = 1000 points
    expect((float) $bookingTx->amount)->toEqual(-1000.0)
        ->and((float) $bookingTx->money_equivalent)->toEqual(-500.0);

    // Refund credit: agency requests a refund, admin approves with a penalty that leaves
    // a refund_amount of 100 money (500 original - 400 penalty), real flow throughout.
    $this->actingAs($agencyUser);
    $refund = app(RefundService::class)->create($agencyUser, new CreateRefundData(
        orderId: $order->id,
        reason: 'Flight cancelled by airline',
    ));

    $this->actingAs($admin);
    $refund = app(RefundService::class)->approve($refund, new DecideRefundData(
        penaltyAmount: 400,
        adminRemarks: 'Partial penalty applied',
    ));

    expect((float) $refund->refund_amount)->toEqual(100.0);

    $refundTx = WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'refund_credit'))->firstOrFail();
    // 100 money * (2 points / 1 money) = 200 points
    expect((float) $refundTx->amount)->toEqual(200.0)
        ->and((float) $refundTx->money_equivalent)->toEqual(100.0);
});

test('DashboardService::getStats sums money_equivalent, not the points amount, for this month financials', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser, 'order' => $order] = seedFinancialSummaryFixtures();

    $this->actingAs($agencyUser);
    $order = app(OrderService::class)->approve($agencyUser, $order->fresh());

    $this->actingAs($agencyUser);
    $refund = app(RefundService::class)->create($agencyUser, new CreateRefundData(
        orderId: $order->id,
        reason: 'Flight cancelled by airline',
    ));

    $this->actingAs($admin);
    app(RefundService::class)->approve($refund, new DecideRefundData(
        penaltyAmount: 400,
        adminRemarks: 'Partial penalty applied',
    ));

    // Raw points sums, computed independently — proves the money-based assertions below
    // are not vacuously true (they would fail if the code still summed "amount").
    $rawPointsBookings = abs((float) WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'booking_debit'))->sum('amount'));
    $rawPointsRefunds = abs((float) WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'refund_credit'))->sum('amount'));
    expect($rawPointsBookings)->toEqual(1000.0)
        ->and($rawPointsRefunds)->toEqual(200.0);

    $stats = app(DashboardService::class)->getStats();

    expect($stats['financials']['totalBookings'])->toEqual(500.0)
        ->and($stats['financials']['totalRefunds'])->toEqual(100.0)
        // The exact assertion that would have failed before the fix (points instead of money).
        ->and($stats['financials']['totalBookings'])->not->toEqual($rawPointsBookings)
        ->and($stats['financials']['totalRefunds'])->not->toEqual($rawPointsRefunds);
});

test('ReportService::summary sums money_equivalent, not the points amount, for the reports page', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser, 'order' => $order] = seedFinancialSummaryFixtures();

    $this->actingAs($agencyUser);
    $order = app(OrderService::class)->approve($agencyUser, $order->fresh());

    $this->actingAs($agencyUser);
    $refund = app(RefundService::class)->create($agencyUser, new CreateRefundData(
        orderId: $order->id,
        reason: 'Flight cancelled by airline',
    ));

    $this->actingAs($admin);
    app(RefundService::class)->approve($refund, new DecideRefundData(
        penaltyAmount: 400,
        adminRemarks: 'Partial penalty applied',
    ));

    $rawPointsBookings = abs((float) WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'booking_debit'))->sum('amount'));
    $rawPointsRefunds = abs((float) WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'refund_credit'))->sum('amount'));

    $filter = new ReportFilterData(
        from: now()->subDay(),
        to: now()->addDay(),
        agencyId: null,
    );

    $summary = app(ReportService::class)->summary($admin, $filter);

    expect($summary['totalBookings'])->toEqual(500.0)
        ->and($summary['totalRefunds'])->toEqual(100.0)
        ->and($summary['net'])->toEqual(400.0)
        ->and($summary['totalBookings'])->not->toEqual($rawPointsBookings)
        ->and($summary['totalRefunds'])->not->toEqual($rawPointsRefunds);
});

test('GET /api/v1/admin/dashboard returns money financials for an authenticated admin', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser, 'order' => $order] = seedFinancialSummaryFixtures();

    $this->actingAs($agencyUser);
    $order = app(OrderService::class)->approve($agencyUser, $order->fresh());

    $this->actingAs($agencyUser);
    $refund = app(RefundService::class)->create($agencyUser, new CreateRefundData(
        orderId: $order->id,
        reason: 'Flight cancelled by airline',
    ));

    $this->actingAs($admin);
    app(RefundService::class)->approve($refund, new DecideRefundData(
        penaltyAmount: 400,
        adminRemarks: 'Partial penalty applied',
    ));

    $response = $this->actingAs($admin)->getJson('/api/v1/admin/dashboard');

    $response->assertOk();
    // json_encode drops the trailing ".0" for whole-number floats, so compare the decoded
    // PHP values rather than the raw JSON string (assertJsonPath does a strict type match).
    expect((float) $response->json('financials.totalBookings'))->toEqual(500.0)
        ->and((float) $response->json('financials.totalRefunds'))->toEqual(100.0);
});

test('GET /api/v1/reports/financial-summary returns money totals for an authenticated admin', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser, 'order' => $order] = seedFinancialSummaryFixtures();

    $this->actingAs($agencyUser);
    $order = app(OrderService::class)->approve($agencyUser, $order->fresh());

    $this->actingAs($agencyUser);
    $refund = app(RefundService::class)->create($agencyUser, new CreateRefundData(
        orderId: $order->id,
        reason: 'Flight cancelled by airline',
    ));

    $this->actingAs($admin);
    app(RefundService::class)->approve($refund, new DecideRefundData(
        penaltyAmount: 400,
        adminRemarks: 'Partial penalty applied',
    ));

    $response = $this->actingAs($admin)->getJson('/api/v1/reports/financial-summary?from='.now()->subDay()->toDateString().'&to='.now()->addDay()->toDateString());

    $response->assertOk();
    expect((float) $response->json('totalBookings'))->toEqual(500.0)
        ->and((float) $response->json('totalRefunds'))->toEqual(100.0)
        ->and((float) $response->json('net'))->toEqual(400.0);
});

/**
 * Regression coverage for a second bug found alongside the points-vs-money one: the
 * financial-summary totals used to filter by WalletTransaction::created_at (the approval
 * date) while the booking-history table above it filters by Order::created_at (the order's
 * creation date). An order created before the filter's "from" date but approved after it
 * would vanish from the visible table while its money still counted in the totals above —
 * numbers that no longer reconciled with what was on screen. walletSum() now joins to the
 * referenced order/refund and filters on ITS created_at, matching the table.
 */
test('ReportService::summary only counts orders whose own created_at falls in range, not the later approval date', function () {
    ['admin' => $admin, 'agencyUser' => $agencyUser, 'order' => $order] = seedFinancialSummaryFixtures();

    // Order created well before the filter window...
    DB::table('orders')->where('id', $order->id)->update(['created_at' => now()->subDays(10)]);

    // ...but approved (and thus debited) inside it.
    $this->actingAs($agencyUser);
    app(OrderService::class)->approve($agencyUser, $order->fresh());

    $filter = new ReportFilterData(from: now()->subDay(), to: now()->addDay(), agencyId: null);

    $orders = app(ReportService::class)->paginatedOrders($admin, $filter);
    expect($orders->total())->toBe(0); // order correctly hidden — created outside the range

    $summary = app(ReportService::class)->summary($admin, $filter);
    expect($summary['totalBookings'])->toEqual(0.0) // must NOT count money from a booking the table doesn't show
        ->and($summary['net'])->toEqual(0.0);

    // Widening the range to cover the order's actual creation date brings it back into both.
    $widerFilter = new ReportFilterData(from: now()->subDays(11), to: now()->addDay(), agencyId: null);
    $widerSummary = app(ReportService::class)->summary($admin, $widerFilter);
    expect($widerSummary['totalBookings'])->toEqual(500.0);
});
