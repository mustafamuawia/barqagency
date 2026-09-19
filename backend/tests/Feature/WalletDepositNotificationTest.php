<?php

use App\Models\AgencyType;
use App\Models\AgencyUser;
use App\Models\PointSetting;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Notifications\Admin\NewWalletDepositNotification;
use App\Services\Wallet\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Regression coverage for the same points-vs-money bug pattern in the "new deposit"
 * admin notification: it must render the real money the agency typed in
 * (WalletTransaction::money_equivalent), not the preview points conversion
 * (WalletTransaction::amount). At submission time `amount` is only a preview — the
 * actual points credited are recomputed at approval time — so under a non-1:1 rate the
 * two columns diverge and the notification used to show the wrong number.
 */
test('NewWalletDepositNotification renders the money amount, not the points preview, under a non-1:1 rate', function () {
    (new \Database\Seeders\ReferenceDataSeeder())->run();

    // Non-1:1 rate: 1 money = 2 points. At 1:1 this test would not distinguish the bug.
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

    Wallet::create([
        'agency_id' => $agency->id,
        'balance' => 0,
        'credit_limit' => 5000,
        'created_by' => $admin->id,
    ]);

    Notification::fake();

    $this->actingAs($agencyUser);
    $tx = app(WalletService::class)->submitDeposit($agencyUser, 300.0, 'REF-12345');

    // The preview points conversion stored on the transaction (300 money * 2 = 600 points) —
    // proving the assertion below is not vacuously true under a coincidental 1:1 rate.
    expect((float) $tx->amount)->toEqual(600.0)
        ->and((float) $tx->money_equivalent)->toEqual(300.0);

    Notification::assertSentTo($admin, NewWalletDepositNotification::class, function (NewWalletDepositNotification $notification) {
        $data = $notification->toArray(null);

        expect($data['message'])->toContain('300.00 ج.س')
            ->and($data['message'])->not->toContain('600.00');

        return true;
    });
});
