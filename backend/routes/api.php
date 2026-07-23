<?php

use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Agency\AgencyController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\ReferenceData\ReferenceDataController;
use App\Http\Controllers\Api\V1\Refund\RefundController;
use App\Http\Controllers\Api\V1\Report\ReportController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::prefix('v1')->group(function () {
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [LoginController::class, 'user']);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store'])->middleware('role:agency');
        Route::put('/orders/{order}/quote', [OrderController::class, 'submitQuote'])->middleware('role:admin');
        Route::put('/orders/{order}/approve', [OrderController::class, 'approveOrder'])->middleware('role:agency');

        // Wallets
        Route::get('/wallets', [WalletController::class, 'myWallet'])->middleware('role:agency');
        Route::get('/wallets/{wallet}', [WalletController::class, 'show'])->middleware('role:admin');
        Route::get('/wallets/agency/{agency}', [WalletController::class, 'showByAgency'])->middleware('role:admin');
        Route::post('/wallets/{wallet}/topup', [WalletController::class, 'topUp'])->middleware('role:admin');
        Route::put('/wallets/{wallet}/credit-limit', [WalletController::class, 'setCreditLimit'])->middleware('role:admin');

        // Agencies
        Route::get('/agencies', [AgencyController::class, 'index'])->middleware('role:admin');
        Route::get('/agencies/{agency}', [AgencyController::class, 'show'])->middleware('role:admin');
        Route::patch('/agencies/{agency}', [AgencyController::class, 'update'])->middleware('role:admin');

        // Refunds
        Route::get('/refunds', [RefundController::class, 'index']);
        Route::get('/refunds/{refund}', [RefundController::class, 'show']);
        Route::post('/refunds', [RefundController::class, 'store'])->middleware('role:agency');
        Route::put('/refunds/{refund}/approve', [RefundController::class, 'approve'])->middleware('role:admin');
        Route::put('/refunds/{refund}/reject', [RefundController::class, 'reject'])->middleware('role:admin');
        Route::get('/refunds/{refund}/credit-note', [RefundController::class, 'creditNote']);

        // Reports
        Route::get('/reports/financial-summary', [ReportController::class, 'financialSummary']);
        Route::get('/reports/financial-summary/pdf', [ReportController::class, 'financialSummaryPdf']);
        Route::get('/reports/financial-summary/csv', [ReportController::class, 'financialSummaryCsv']);

        // Reference data
        Route::get('/airlines', [ReferenceDataController::class, 'airlines']);
        Route::get('/airports', [ReferenceDataController::class, 'airports']);
        Route::get('/cabin-classes', [ReferenceDataController::class, 'cabinClasses']);
        Route::get('/passenger-types', [ReferenceDataController::class, 'passengerTypes']);

        // Admin dashboard
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware('role:admin');
    });
});
