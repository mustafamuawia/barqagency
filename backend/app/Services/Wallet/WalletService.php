<?php

namespace App\Services\Wallet;

use App\DTOs\Wallet\TopUpData;
use App\Exceptions\MissingAgencyException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransactionType;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

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
            // Lock the wallet row to prevent concurrent top-ups producing wrong balance_after
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            $type = WalletTransactionType::firstOrCreate(['name' => 'topup'], ['label' => 'Top-Up']);
            $balanceBefore = (float) $wallet->balance;
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

            $this->audit->log('wallet.topup', $wallet, ['balance' => $balanceBefore], [
                'amount' => (float) $data->amount,
                'balance' => (float) $newBalance,
            ]);
        });

        return $wallet->fresh();
    }

    public function setCreditLimit(Wallet $wallet, float $creditLimit): Wallet
    {
        $previousLimit = (float) $wallet->credit_limit;

        $wallet->update(['credit_limit' => $creditLimit]);

        $this->audit->log('wallet.credit_limit_changed', $wallet, ['credit_limit' => $previousLimit], ['credit_limit' => $creditLimit]);

        return $wallet;
    }
}
