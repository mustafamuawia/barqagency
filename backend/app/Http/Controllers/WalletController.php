<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\WalletTransactionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletController extends Controller
{
    public function myWallet(Request $request)
    {
        $user = $request->user();
        $agency = $user->agencies()->first();

        if (!$agency) {
            return response()->json(['message' => 'No agency associated with user'], 404);
        }

        $wallet = $agency->wallet;
        $wallet->load(['transactions' => fn($q) => $q->with('transactionType')->limit(50)]);
        $wallet->status;

        return response()->json($wallet);
    }

    public function show(Wallet $wallet)
    {
        $wallet->load(['agency', 'transactions' => fn($q) => $q->with('transactionType')->limit(50)]);
        return response()->json($wallet);
    }

    public function showByAgency($agencyId)
    {
        $wallet = Wallet::where('agency_id', $agencyId)->firstOrFail();
        $wallet->load(['agency', 'transactions' => fn($q) => $q->with('transactionType')->limit(50)]);
        return response()->json($wallet);
    }

    public function topUp(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($wallet, $validated) {
            $type = WalletTransactionType::firstOrCreate(['name' => 'topup'], ['label' => 'Top-Up']);
            $newBalance = $wallet->balance + $validated['amount'];

            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $type->id,
                'amount' => $validated['amount'],
                'balance_after' => $newBalance,
                'description' => $validated['description'] ?? 'Wallet top-up',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $wallet->update(['balance' => $newBalance]);
        });

        return response()->json([
            'message' => 'Wallet topped up successfully',
            'wallet' => $wallet->fresh(),
        ]);
    }

    public function setCreditLimit(Request $request, Wallet $wallet)
    {
        $validated = $request->validate([
            'credit_limit' => 'required|numeric|min:0',
        ]);

        $wallet->update(['credit_limit' => $validated['credit_limit']]);

        return response()->json([
            'message' => 'Credit limit updated',
            'wallet' => $wallet,
        ]);
    }
}
