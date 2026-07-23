<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TravelAgency;
use App\Models\Wallet;

class DashboardController extends Controller
{
    public function index()
    {
        $pendingOrders = Order::pendingPricing()->count();
        $totalBalance = Wallet::sum('balance');

        $recentOrders = Order::with(['agency', 'status'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $agenciesAtRisk = TravelAgency::with('wallet')
            ->get()
            ->filter(fn($a) => $a->wallet && $a->wallet->status !== 'healthy')
            ->take(5)
            ->values();

        return response()->json([
            'stats' => [
                'pendingOrders' => $pendingOrders,
                'pendingRefunds' => 0,
                'pendingTopups' => 0,
                'totalBalance' => $totalBalance,
            ],
            'financials' => [
                'totalBookings' => 0,
                'totalRefunds' => 0,
            ],
            'recentOrders' => $recentOrders,
            'agenciesAtRisk' => $agenciesAtRisk,
        ]);
    }
}
