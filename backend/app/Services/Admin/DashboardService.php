<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Refund;
use App\Models\TravelAgency;
use App\Models\Wallet;
use App\Models\WalletTransaction;

class DashboardService
{
    public function getStats(): array
    {
        $pendingOrders = Order::pendingPricing()->count();
        $pendingRefunds = Refund::pending()->count();
        $pendingAgencies = TravelAgency::where('status', 'pending')->count();
        $totalBalance = Wallet::sum('balance');

        $recentOrders = Order::with(['agency', 'status'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $agenciesAtRisk = TravelAgency::with('wallet')
            ->get()
            ->filter(fn ($a) => $a->wallet && $a->wallet->status !== 'healthy')
            ->take(5)
            ->values();

        $totalBookings = WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'booking_debit'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $totalRefunds = WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', 'refund_credit'))
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        return [
            'stats' => [
                'pendingOrders' => $pendingOrders,
                'pendingRefunds' => $pendingRefunds,
                'pendingAgencies' => $pendingAgencies,
                'pendingTopups' => 0,
                'totalBalance' => $totalBalance,
            ],
            'financials' => [
                'totalBookings' => abs($totalBookings),
                'totalRefunds' => abs($totalRefunds),
            ],
            'recentOrders' => $recentOrders,
            'agenciesAtRisk' => $agenciesAtRisk,
        ];
    }
}
