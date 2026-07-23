<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\ReferenceDataController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn() => response()->json(['status' => 'ok']));

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::get('/user', [LoginController::class, 'user']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::put('/orders/{order}/quote', [OrderController::class, 'submitQuote']);
    Route::put('/orders/{order}/approve', [OrderController::class, 'approveOrder']);

    // Wallets
    Route::get('/wallets', [WalletController::class, 'myWallet']);
    Route::get('/wallets/{wallet}', [WalletController::class, 'show']);
    Route::get('/wallets/agency/{agency}', [WalletController::class, 'showByAgency']);
    Route::post('/wallets/{wallet}/topup', [WalletController::class, 'topUp']);
    Route::put('/wallets/{wallet}/credit-limit', [WalletController::class, 'setCreditLimit']);

    // Agencies
    Route::get('/agencies', [AgencyController::class, 'index']);
    Route::get('/agencies/{agency}', [AgencyController::class, 'show']);
    Route::patch('/agencies/{agency}', [AgencyController::class, 'update']);

    // Reference data
    Route::get('/airlines', [ReferenceDataController::class, 'airlines']);
    Route::get('/airports', [ReferenceDataController::class, 'airports']);
    Route::get('/cabin-classes', [ReferenceDataController::class, 'cabinClasses']);
    Route::get('/passenger-types', [ReferenceDataController::class, 'passengerTypes']);

    // Admin dashboard
    Route::get('/admin/dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);
});
