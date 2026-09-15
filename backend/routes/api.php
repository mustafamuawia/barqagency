<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Agency\AgencyController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\FlightSearch\FlightSearchController;
use App\Http\Controllers\Api\V1\Order\OrderController;
use App\Http\Controllers\Api\V1\ReferenceData\ReferenceDataController;
use App\Http\Controllers\Api\V1\Refund\RefundController;
use App\Http\Controllers\Api\V1\Report\ReportController;
use App\Http\Controllers\Api\V1\Settings\BankAccountController;
use App\Http\Controllers\Api\V1\Settings\PointSettingController;
use App\Http\Controllers\Api\V1\Wallet\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::prefix('v1')->group(function () {
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/register', [\App\Http\Controllers\Api\V1\Auth\RegisterController::class, 'registerAgency'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/verify-email', [\App\Http\Controllers\Api\V1\Auth\EmailVerificationController::class, 'verify'])->middleware('throttle:10,1');
    Route::get('/flight-search', [FlightSearchController::class, 'search'])->middleware('throttle:30,1');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/user', [LoginController::class, 'user']);
        Route::post('/resend-verification', [\App\Http\Controllers\Api\V1\Auth\EmailVerificationController::class, 'resend'])->middleware('throttle:5,1');

        // Profile
        Route::get('/profile', [\App\Http\Controllers\Api\V1\Auth\ProfileController::class, 'show']);
        Route::put('/profile', [\App\Http\Controllers\Api\V1\Auth\ProfileController::class, 'update']);
        Route::put('/profile/password', [\App\Http\Controllers\Api\V1\Auth\ProfileController::class, 'updatePassword']);

        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders', [OrderController::class, 'store'])->middleware(['role:agency', 'agency.active']);
        Route::post('/orders/book-itinerary', [OrderController::class, 'bookItinerary'])->middleware(['role:agency', 'agency.active']);
        Route::put('/orders/{order}/quote', [OrderController::class, 'submitQuote'])->middleware('role:admin');
        Route::put('/orders/{order}/approve', [OrderController::class, 'approveOrder'])->middleware(['role:agency', 'agency.active']);
        Route::post('/orders/{order}/ticket', [OrderController::class, 'uploadTicket'])->middleware('role:admin');
        Route::get('/orders/{order}/ticket', [OrderController::class, 'downloadTicket']);

        // Wallets
        Route::get('/wallets', [WalletController::class, 'myWallet'])->middleware(['role:agency', 'agency.active']);
        Route::post('/wallets/deposit', [WalletController::class, 'submitDeposit'])->middleware(['role:agency', 'agency.active']);
        Route::get('/wallets/my-deposits', [WalletController::class, 'myDeposits'])->middleware(['role:agency', 'agency.active']);
        Route::get('/wallets/my-deposits/pdf', [WalletController::class, 'myDepositsPdf'])->middleware(['role:agency', 'agency.active']);
        Route::get('/wallets/my-deposits/csv', [WalletController::class, 'myDepositsCsv'])->middleware(['role:agency', 'agency.active']);
        Route::get('/wallets/transactions', [WalletController::class, 'myTransactions'])->middleware(['role:agency', 'agency.active']);
        Route::get('/wallets/pending-deposits', [WalletController::class, 'pendingDeposits'])->middleware('role:admin');
        Route::get('/wallets/all-deposits', [WalletController::class, 'allDeposits'])->middleware('role:admin');
        Route::get('/wallets/all-deposits/pdf', [WalletController::class, 'allDepositsPdf'])->middleware('role:admin');
        Route::get('/wallets/all-deposits/csv', [WalletController::class, 'allDepositsCsv'])->middleware('role:admin');
        Route::put('/wallets/deposits/{id}/approve', [WalletController::class, 'approveDeposit'])->middleware('role:admin');
        Route::put('/wallets/deposits/{id}/reject', [WalletController::class, 'rejectDeposit'])->middleware('role:admin');
        Route::get('/wallets/{wallet}', [WalletController::class, 'show'])->middleware('role:admin');
        Route::get('/wallets/agency/{agency}', [WalletController::class, 'showByAgency'])->middleware('role:admin');
        Route::post('/wallets/{wallet}/topup', [WalletController::class, 'topUp'])->middleware('role:admin');
        Route::put('/wallets/{wallet}/credit-limit', [WalletController::class, 'setCreditLimit'])->middleware('role:admin');

        // Agencies
        Route::get('/agencies', [AgencyController::class, 'index'])->middleware('role:admin');
        Route::get('/agencies/{agency}', [AgencyController::class, 'show'])->middleware('role:admin');
        Route::patch('/agencies/{agency}', [AgencyController::class, 'update'])->middleware('role:admin');
        Route::put('/agencies/{agency}/approve', [AgencyController::class, 'approve'])->middleware('role:admin');
        Route::put('/agencies/{agency}/reject', [AgencyController::class, 'reject'])->middleware('role:admin');

        // Refunds
        Route::get('/refunds', [RefundController::class, 'index']);
        Route::get('/refunds/{refund}', [RefundController::class, 'show']);
        Route::post('/refunds', [RefundController::class, 'store'])->middleware(['role:agency', 'agency.active']);
        Route::put('/refunds/{refund}/approve', [RefundController::class, 'approve'])->middleware('role:admin');
        Route::put('/refunds/{refund}/reject', [RefundController::class, 'reject'])->middleware('role:admin');
        Route::get('/refunds/{refund}/credit-note', [RefundController::class, 'creditNote']);

        // Point settings (money-to-points conversion rate for the wallet)
        Route::get('/point-settings', [PointSettingController::class, 'show']);
        Route::put('/point-settings', [PointSettingController::class, 'update'])->middleware('role:super_admin');

        // Bank accounts (transfer instructions shown to agencies on the wallet page)
        Route::get('/bank-accounts', [BankAccountController::class, 'index']);
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/admin/bank-accounts', [BankAccountController::class, 'adminIndex']);
            Route::post('/admin/bank-accounts', [BankAccountController::class, 'store']);
            Route::put('/admin/bank-accounts/{bankAccount}', [BankAccountController::class, 'update']);
            Route::delete('/admin/bank-accounts/{bankAccount}', [BankAccountController::class, 'destroy']);
        });

        // Reports
        Route::get('/reports/financial-summary', [ReportController::class, 'financialSummary']);
        Route::get('/reports/financial-summary/pdf', [ReportController::class, 'financialSummaryPdf']);
        Route::get('/reports/financial-summary/csv', [ReportController::class, 'financialSummaryCsv']);
        Route::get('/reports/booking-history', [ReportController::class, 'bookingHistory']);

        // Reference data
        Route::get('/airlines', [ReferenceDataController::class, 'airlines']);
        Route::get('/airports', [ReferenceDataController::class, 'airports']);
        Route::get('/cabin-classes', [ReferenceDataController::class, 'cabinClasses']);
        Route::get('/passenger-types', [ReferenceDataController::class, 'passengerTypes']);
        Route::get('/countries', [ReferenceDataController::class, 'countries']);

        // Admin dashboard
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware('role:admin');

        // Admin real-time notifications
        Route::middleware('role:admin')->group(function () {
            Route::get('/admin/notifications', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'index']);
            Route::get('/admin/notifications/unread-count', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'unreadCount']);
            Route::put('/admin/notifications/read-all', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'markAllRead']);
            Route::put('/admin/notifications/{id}/read', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'markRead']);
        });

        // Agency real-time notifications (same generic controller/service, scoped to the authenticated user)
        Route::middleware(['role:agency', 'agency.active'])->group(function () {
            Route::get('/agency/notifications', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'index']);
            Route::get('/agency/notifications/unread-count', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'unreadCount']);
            Route::put('/agency/notifications/read-all', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'markAllRead']);
            Route::put('/agency/notifications/{id}/read', [\App\Http\Controllers\Api\V1\Admin\AppNotificationController::class, 'markRead']);
        });

        // Admin user management (super admin only)
        Route::middleware('role:super_admin')->group(function () {
            Route::get('/admin/users', [UserController::class, 'index']);
            Route::post('/admin/users', [UserController::class, 'store']);
            Route::get('/admin/users/{user}', [UserController::class, 'show']);
            Route::put('/admin/users/{user}', [UserController::class, 'update']);
            Route::put('/admin/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
            Route::put('/admin/users/{id}/restore', [UserController::class, 'restore']);
            Route::delete('/admin/users/{user}', [UserController::class, 'destroy']);
        });

        // Admin audit trail
        Route::get('/admin/audit-logs', [AuditLogController::class, 'index'])->middleware('role:admin');
        Route::get('/admin/login-history', [AuditLogController::class, 'loginHistory'])->middleware('role:admin');
    });
});
