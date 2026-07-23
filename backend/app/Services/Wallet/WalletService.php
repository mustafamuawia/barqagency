<?php

namespace App\Services\Wallet;

use App\DTOs\Wallet\TopUpData;
use App\Exceptions\MissingAgencyException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function myWallet(User $user): Wallet
    {
        $agency = $user->agencies()->first();

        if (!$agency) {
            throw new MissingAgencyException('No agency associated with user', 404);
        }

        $wallet = $agency->wallet;
        $wallet->load(['transactions' => fn ($q) => $q->with('transactionType')->limit(50)]);
        $wallet->status;

        return $wallet;
    }

    public function find(Wallet $wallet): Wallet
    {
        $wallet->load(['agency', 'transactions' => fn ($q) => $q->with('transactionType')->limit(50)]);

        return $wallet;
    }

    public function findByAgency(int $agencyId): Wallet
    {
        $wallet = Wallet::where('agency_id', $agencyId)->firstOrFail();
        $wallet->load(['agency', 'transactions' => fn ($q) => $q->with('transactionType')->limit(50)]);

        return $wallet;
    }

    public function topUp(Wallet $wallet, TopUpData $data): Wallet
    {
        DB::transaction(function () use ($wallet, $data) {
            $type = WalletTransactionType::firstOrCreate(['name' => 'topup'], ['label' => 'Top-Up']);
            $newBalance = $wallet->balance + $data->amount;

            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $type->id,
                'amount' => $data->amount,
                'balance_after' => $newBalance,
                'description' => $data->description ?? 'Wallet top-up',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $wallet->update(['balance' => $newBalance]);
        });

        return $wallet->fresh();
    }

    public function setCreditLimit(Wallet $wallet, float $creditLimit): Wallet
    {
        $wallet->update(['credit_limit' => $creditLimit]);

        return $wallet;
    }
}
